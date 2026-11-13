# Console Commands

Wishlist includes console commands you can run from your Craft project’s directory.

## Lists

::: reference
### `wishlist/lists/purge-inactive-lists`

Deletes lists that have been inactive for longer than the [purge settings](docs:get-started/configuration) allow, along with their items, and reports how many lists were deleted. Wishlist already does this during Craft’s garbage collection, so run the command when you want to purge lists straight away, such as from a scheduled task. It deletes nothing when `purgeInactiveLists` is `false`.

```shell
php craft wishlist/lists/purge-inactive-lists
```
:::

## Items

::: reference
### `wishlist/items/cleanup-orphaned-items`

Removes item elements whose item record no longer exists, then removes items whose linked element has been deleted. Run it to tidy up items left behind after lists or linked elements were deleted outside Craft’s usual element deletion.

```shell
php craft wishlist/items/cleanup-orphaned-items
```
:::

## Resaving

Wishlist adds two actions to Craft’s `resave` command, which accept Craft’s usual [resave options](https://craftcms.com/docs/5.x/reference/cli.html#resave), such as `--queue` and `--limit`.

::: reference
### `resave/wishlist-lists`

Re-saves Wishlist lists.

```shell
php craft resave/wishlist-lists --queue
```
:::

::: reference
### `resave/wishlist-items`

Re-saves Wishlist items.

| Option | Description | Default |
| --- | --- | --- |
| `--list-id` | Only re-saves items in the lists with these IDs, separated by commas. | All lists |

```shell
php craft resave/wishlist-items --list-id=12,15
```
:::
