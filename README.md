# FastAsyncWorldEdit (FAWE)

This repository is a **PocketMine-MP fork of [EasyEdit](https://github.com/platz1de/EasyEdit)**. It keeps EasyEdit's commands and editing tools, with the selection changes described below. It is not the official Java FAWE plugin.

## Changes in this fork

- **Multiple cuboid regions:** use the selection axe to mark corners. Sneak and left-click to start another region, then right-click to finish it. Editing and selection commands apply to all selected regions.
- **Non-cuboid selections:** `//sel poly` and `//sel convex` provide polygon and convex selections with matching outlines.
- **Quick clear:** sneak and drop the selection axe, or run `//unselect`, to clear the selection.
- **Existing installs:** the plugin is now named `FastAsyncWorldEdit`.

## Use and installation

Install this fork on a PocketMine-MP server. For the full command list, configuration details and usage inherited from EasyEdit, see the [EasyEdit README](https://github.com/platz1de/EasyEdit#readme) and [wiki](https://github.com/platz1de/EasyEdit/wiki).

The plugin requires PocketMine-MP API 5. The selection wand is obtained with `//wand`.

## Project links

- [EasyEdit upstream](https://github.com/platz1de/EasyEdit)
- [This fork](https://github.com/Iridia-Bedrock/EasyEdit)
