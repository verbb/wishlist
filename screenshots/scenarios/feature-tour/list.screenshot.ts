import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedWishlistFixture } from '../../support/fixtures';

let listRoute = '/admin/wishlist/lists/wishlist';

export default defineScreenshotScenario({
    id: 'wishlist-feature-tour-list',
    output: 'feature-tour/wishlist-list.png',
    route: () => listRoute,
    viewport: { width: 1440, height: 900, deviceScaleFactor: 2 },
    async setup(context) {
        listRoute = (await seedWishlistFixture(context)).listRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Saved for the weekend' },
        { type: 'text', text: 'A guide to Melbourne’s hidden gardens' },
        { type: 'text', text: 'Six coastal walks for a quiet weekend' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `
                (() => {
                    document.activeElement?.blur();
                    window.scrollTo(0, 0);
                })();
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: {
        type: 'anchoredClip',
        selector: '#main-container',
        x: 0,
        y: 0,
        width: 1210,
        height: 760,
    },
    caption: 'A single Wishlist list populated with five saved Craft entries.',
    intent: 'Show the genuine Craft 5 list editor and item index without requiring Craft Commerce.',
});
