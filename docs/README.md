# Surface Core Module

## Overview

The Surface module allows admins to **control the visibility of any UI element** on a per-user or global basis. Admins can double-click any element on the page to configure its visibility rules without touching code.

## Features

### Admin Interface
- **Double-click any element** to open the visibility configuration modal
- Hover highlighting shows which element will be targeted before you click
- Tooltip indicator confirms double-click is available on hover
- Scoped path selector generation ensures sibling elements (e.g. individual nav items) are targeted individually, not as a group

### Rule Management
- Create **global** (all users) or **user-specific** visibility rules
- View and inline-delete existing rules for any container from within the modal
- Displays assigned user and rule scope for each existing rule
- Rules persist across sessions via database storage

### Rule Application
- Rules apply on page load for all affected users
- `MutationObserver` re-applies rules to dynamically injected content (stream items, widgets) without a page reload
- Supports any valid CSS selector — class, ID, scoped path, or legacy attribute selector

### Selector Engine
- Automatically generates a unique scoped CSS path per element (e.g. `#top-menu > ul.navbar-nav > li.nav-item:nth-child(3)`)
- Walks up the DOM to the nearest ID-anchored ancestor for selector stability
- Falls back gracefully to legacy selector formats stored in previous versions

## Usage

1. Log in as an admin and navigate to any page
2. Double-click any element — a configuration modal will appear
3. Choose to disable for **all users** or select a **specific user**
4. Click **Save** — the rule is applied immediately on next page load for affected users

> [!NOTE]
> Previously saved rules using the old `class-` prefix format remain fully supported via legacy fallback resolution.

## Future Enhancements

- [ ] **Real-time Rule Application** – Apply and remove rules without page reload
- [ ] **Batch Operations** – Delete multiple rules at once
- [ ] **Rule Editing** – Edit existing rules instead of delete + recreate
- [ ] **Preview Mode** – Preview what a container looks like when hidden before saving
- [ ] **Rule History** – Track when rules were created and deleted for audit purposes
- [ ] **Role-based Rules** – Target rules at HumHub user groups/roles rather than individuals
- [ ] **Export / Import** – Backup and restore rule sets across environments
