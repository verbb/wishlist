import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import type { ScreenshotSetupContext } from '@verbb/craft-screenshots/types';

type WishlistFixture = {
    listRoute: string;
    itemCount: number;
};

const supportDir = dirname(fileURLToPath(import.meta.url));
const seedScript = readFileSync(join(supportDir, 'seed', 'seed-wishlist.php'), 'utf8');

/** Seed one genuine Wishlist list backed by ordinary Craft entries. */
export async function seedWishlistFixture(context: ScreenshotSetupContext): Promise<WishlistFixture> {
    const output = await context.runCraftScript(seedScript, { label: 'seed-wishlist' });
    const fixture = JSON.parse(output.trim()) as WishlistFixture;

    if (!fixture.listRoute || fixture.itemCount !== 5) {
        throw new Error(`Invalid Wishlist fixture payload: ${output}`);
    }

    return fixture;
}
