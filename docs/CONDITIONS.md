# Guide to setting CodeSlot rules

Conditions answer: **where, or for which visitors, should this snippet run?** They filter the insertion location you already selected. They do not insert a shortcode, activate a snippet or override Safe Mode.

## First example: one page

To run code only on `https://example.com/contact/`:

1. Open **CodeSlot → Add New**, or edit your snippet.
2. Choose the code type and an appropriate insertion method/location. For a script that belongs in the page head, use **Auto Insert → Site-wide Header**. Use **HTML** for complete `<script>…</script>` embeds, or **JavaScript** for the code inside the tags.
3. Under **Conditions → Include if any group matches**, click **OR group**.
4. Choose **Specific post or page**, leave the operator as **is**, and type at least **2 characters** of its title. A word or two is usually enough.
5. Select the actual item from the results. Each result shows its content type and ID so you can distinguish identical titles. Products and other plugins' public content items appear here too.
6. Leave **Exclude** empty. Activate and save the snippet. Purge your page/CDN cache, then open that item's public URL.

You do **not** need to type an ID. Search text alone is not a selection: click a result, or use **Arrow Down/Up → Enter**. **Escape** closes the results. Use **Clear selection** to change the item. Searches show at most 20 matches; type more of the title to narrow them down. Search errors show a retry/reload message rather than silently choosing another item.

The URL field shows **Example URL path: /your-page/**. This is a placeholder: replace it with your actual page path.

Alternatively, choose **URL path → is → `/contact/`**. Enter only the exact site-relative path. Do not enter `https://example.com`, `?query=value` or `#fragment`. Keep the trailing slash if your public URL has it. On a site in a subdirectory, include that subdirectory in the path. Wildcards and partial-path matching are not supported.

## Which option should I choose?

| Condition shown in the editor | What it matches | What to enter/select next |
|---|---|---|
| **Entire site** | No extra restriction at the chosen insertion location. | Nothing. No value field is needed. |
| **Website visitors** | Visitor-facing requests outside the dashboard. | Nothing. |
| **WordPress dashboard** | WordPress administration requests. | Nothing. Select an admin insertion location for HTML/CSS/JavaScript. |
| **Front page** | The website's main landing page, for example `example.com/`. | Nothing. |
| **Blog Posts Page** | The page listing your blog posts. | Nothing. See the explanation below. |
| **Specific post or page** | One individual post, page, product or other public content item. | Search its **title**, then select it. The numeric identity is saved automatically. |
| **Post type** | Individual items belonging to one public content type. It does not include that type's archive. | Search the type's name, such as **Posts**, **Pages**, **Products** or a plugin's **Books**. Select the result. |
| **Category or tag** | Items directly assigned the chosen term, and that term's own archive. | Search the term's **name**, then select it. The result identifies its taxonomy and ID. You do not type `category:12` yourself. Parent terms do not automatically include every descendant term. |
| **Archive page** | WordPress category, tag, author, date and post-type archives. | Nothing. |
| **Search results** | Website search results. | Nothing. |
| **Page not found (404)** | A request WordPress identifies as a missing page. | Nothing. |
| **Logged-in visitor** | A visitor signed into WordPress. | Nothing. Choose **is not** for logged-out visitors. |
| **Visitor role** | A signed-in visitor with the selected WordPress role. | Search a role's readable name, for example **Administrator**, **Customer** or a plugin's custom role, and select it. |
| **URL path** | The exact request path. Query strings are ignored. | Type `/contact/`, `/your-page/`, `/` or another exact path. |
| **Mobile visitor** | WordPress considers the visitor's device mobile. | Nothing. Configure page caches to vary by device. |
| **WooCommerce Shop** | The main WooCommerce shop page. | Nothing. Requires active WooCommerce. |
| **WooCommerce Cart** | The WooCommerce cart page. | Nothing. Requires active WooCommerce. |
| **WooCommerce Checkout** | Checkout requests, including endpoints such as order-received where WooCommerce identifies them as checkout. | Nothing. Requires active WooCommerce. Use an exact URL rule if you need a narrower page. |
| **WooCommerce My Account** | My Account and its endpoints. | Nothing. Requires active WooCommerce. |

### Front page versus Blog Posts Page

Check **WordPress → Settings → Reading**:

- **Your latest posts**: the main landing page is also the Blog Posts Page. Both rules can match it.
- **A static page**: **Front page** matches the selected homepage; **Blog Posts Page** matches the separate page assigned as the posts page, such as `/blog/`.

A normal individual blog article is not the Blog Posts Page. To target all individual articles, choose **Post type → Posts**.

### Products and other plugins

There is no separate Product rule. For **all individual products**, choose **Post type → Products**. For **one product**, choose **Specific post or page** and search its title.

Searches use the public post types and taxonomies currently registered by WordPress and active plugins, plus registered roles. Private internal types, attachments, Trash and content you cannot edit are excluded. Search is by title/name; body text is not searched. Newly registered public types appear automatically without adding a CodeSlot integration. If an expected item is absent, check that its plugin is active and exposes that type/taxonomy as public.

Shop, Cart, Checkout and My Account remain visible but disabled when WooCommerce is unavailable. Activate WooCommerce to use them. Any saved unavailable dependency pauses the whole snippet, including Exclude and alternative OR groups. See [missing plugins and saved setup](DEPENDENCIES.md). A custom plugin's public Product-like type can still appear in the general searches without WooCommerce.

Long titles are limited to two lines in the results. After selection, the field stays compact; hover or focus within it to inspect the full title and identity. Results scroll inside the rule group on desktop and mobile. A deleted item or deactivated content provider shows **Unavailable selection** when reopening its rule and pauses the snippet; restore it or explicitly clear/select a valid item and save. Names are display labels; stored IDs/slugs determine matching.

## Include, Exclude, AND and OR

- **Include**: if empty, there is no extra condition restriction. Otherwise, at least one Include group must match.
- **AND rule**: adds another requirement to the same group. **Every rule in that group must match.**
- **OR group**: creates an alternative group. **Any matching group is enough.**
- **Exclude**: if any Exclude group matches, the snippet is blocked, even if Include also matches.
- **is / is not**: matches a condition or reverses its result. For example, **Logged-in visitor → is not** means logged out.

### Example: products for logged-in customers

One Include group:

1. **Post type → is → Products**.
2. Click **AND rule**; choose **Visitor role → is → Customer**.

Both requirements must match. A product page viewed by a visitor without that role will not qualify.

### Example: either of two pages

Create an Include group selecting the first page. Click **OR group** outside that group and select the second page. The snippet can run on either page. Putting two different Specific post or page rules into the same AND group cannot match: a request cannot be both individual items at once.

### Example: everywhere except the blog listing

Leave Include empty. Under Exclude, create a group with **Blog Posts Page → is**. The chosen insertion location is allowed elsewhere but excluded on the posts listing.

### Example: one category's individual blog articles

In one Include group select **Category or tag → is → your category**, then add **AND → Post type → is → Posts**. This includes assigned individual articles and excludes the category archive because an archive is not an individual post.

## If the code does not appear

1. Save the snippet and confirm it is **Active**. Check **Settings → Safe Mode**.
2. Check the **insertion method/location**. A Shortcode snippet needs its shortcode placed in content; On Demand needs its matching action. Conditions only filter execution.
3. Check Include/Exclude, device and schedule settings. End dates are exclusive; the WordPress site timezone applies.
4. Open the final public URL after redirects. Exact URL paths distinguish `/your-page` from `/your-page/`.
5. Purge LiteSpeed/other page caches and CDN caches. Cached HTML may predate your changes. Role/login/device rules need corresponding cache variation or cache bypass; rules do not secure private content.
6. For HTML/inline JavaScript/CSS in the header or footer, inspect the page source for the **CodeSlot: Snippet #ID** comments. Static-file mode shows a script/style file reference. Selector insertion is applied in the browser: inspect the live DOM. PHP executes on the server, so its PHP source is never printed.
7. Check the snippet's error details and the relevant CodeSlot logs. A rule match cannot force a theme to call a missing insertion hook.

Conditions are filters for administrator-authored snippets, not replacements for WordPress permissions, consent settings or WooCommerce conversion event settings.

## Required plugins

For custom code that calls another plugin’s APIs, open **Required plugins (optional)** and select that plugin before saving. Missing dependencies pause execution without clearing your saved configuration. [Full dependency guide](DEPENDENCIES.md).
