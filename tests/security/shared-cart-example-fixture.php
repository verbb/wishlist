<?php
// Run with WISHLIST_TEST_VENDOR pointing to an installed Craft vendor directory.
$vendor = getenv('WISHLIST_TEST_VENDOR');
if (!$vendor || !is_file($vendor . '/autoload.php')) {
    throw new RuntimeException('Set WISHLIST_TEST_VENDOR.');
}
require $vendor . '/autoload.php';

$guide = file_get_contents(dirname(__DIR__, 2) . '/docs/template-guides/add-to-cart.md');
$section = explode('## Adding a Shared List to the Cart', $guide, 2)[1] ?? '';
if (!preg_match('/```twig\n(.*?)\n```/s', $section, $match)) {
    throw new RuntimeException('Shared-cart Twig example not found.');
}
$twig = new Twig\Environment(new Twig\Loader\ArrayLoader(['example' => $match[1]]), [
    'strict_variables' => true,
    'autoescape' => 'html',
]);
$twig->addFunction(new Twig\TwigFunction('csrfInput', fn() => '<input name="CRAFT_CSRF_TOKEN" value="test">', ['is_safe' => ['html']]));
$twig->addFunction(new Twig\TwigFunction('redirectInput', fn($url) => '<input name="redirect" value="cart">', ['is_safe' => ['html']]));

class FixtureLists
{
    public array $calls = [];
    public function lists(bool $forUser = true): object
    {
        $this->calls[] = ['lists', $forUser];
        return new class($this) {
            private mixed $reference = null;
            public function __construct(private FixtureLists $owner) {}
            public function reference($value): self { $this->reference = $value; $this->owner->calls[] = ['reference', $value]; return $this; }
            public function one(): ?object
            {
                $this->owner->calls[] = ['one'];
                // Model the actual query contract: null means no reference predicate.
                return match ($this->reference) {
                    null => (object)['id' => 99, 'reference' => 'PRIVATE-REFERENCE'],
                    'VALID-REFERENCE' => (object)['id' => 42, 'reference' => 'VALID-REFERENCE'],
                    default => null,
                };
            }
        };
    }
}
$count = 0;
foreach ([null, '', false, [], ['VALID-REFERENCE'], ['id' => 'VALID-REFERENCE'], '0', 0, true, 1.5, 'INVALID-REFERENCE', 'VALID-REFERENCE'] as $reference) {
    $lists = new FixtureLists();
    $request = new class($reference) {
        public function __construct(private mixed $reference) {}
        public function getParam(string $key): mixed { return $this->reference; }
    };
    $html = $twig->render('example', ['craft' => (object)['app' => (object)['request' => $request], 'wishlist' => $lists]]);
    $valid = $reference === 'VALID-REFERENCE';
    if (str_contains($html, 'PRIVATE-REFERENCE') || str_contains($html, '<form') !== $valid) {
        throw new RuntimeException('Unsafe or missing output for ' . json_encode($reference));
    }
    if (($reference === null || $reference === '' || $reference === false || is_array($reference)) && $lists->calls !== []) {
        throw new RuntimeException('Invalid credential triggered a query.');
    }
    foreach ($lists->calls as $call) {
        if ($call[0] === 'reference' && !is_string($call[1])) {
            throw new RuntimeException('Reference query received a non-string scalar.');
        }
    }
    if ($valid && (!str_contains($html, 'value="42"') || !str_contains($html, 'value="VALID-REFERENCE"') || !str_contains($html, 'method="POST"') || !str_contains($html, 'CRAFT_CSRF_TOKEN'))) {
        throw new RuntimeException('Valid shared-list form lost its credentials or POST/CSRF inputs.');
    }
    $count++;
    echo 'PASS: ' . json_encode($reference) . "\n";
}
echo "Completed $count Twig rendering cases.\n";
