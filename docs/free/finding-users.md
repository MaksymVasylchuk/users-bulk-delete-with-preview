# Finding users

Start on the **Bulk Users Delete** page. **Choose filter type** decides which form is shown.

## Choose from existing users

Pick users by name, login or email in **Select existing users**, or tick **All users of this site**.

## Find users according to certain criteria

The criteria are grouped. Every filter you set narrows the result (they are combined with AND). At least one filter is required.

### Account

| Filter | Options |
|---|---|
| **User Role** | One or more roles |
| **User Email** | Equal to, Not equal to, Like, Not like, Ends with (for example `@example.com`) |
| **User Registration Date** | On or after, On or before, On a day, Between two dates. Dates use the site's timezone. |

### Activity

| Filter | What it finds |
|---|---|
| **Only users without posts or comments on this site** | Accounts that never contributed anything |
| **Only users without WooCommerce orders** | Customers who never ordered (WooCommerce only) |

### Advanced: User Meta

Pick a meta key (for example `first_name`, `billing_country`) and a comparison:

- **Meta does not exist**, **Meta is empty or missing**
- Text: Equal to, Not equal to, Like, Not like
- Date: Equal to, Not equal to, Less than, Less than or equal to, Greater than, Greater than or equal to
- Number: the same comparisons as dates

::: tip
**Meta is empty or missing** is usually what you want for profile fields: WordPress saves empty values for fields the user never filled in.
:::

The **Spam signs** and **Fraud signals** groups are filled by the [Pro add-on](/pro/filters).

## Find users using WooCommerce filters

Available when WooCommerce is active.

- **Select products that bought user**: customers who bought one of the chosen products (search as you type), or **Select All** for customers who bought any product.
- Only paid orders count, with both HPOS and legacy order storage.

## Who is never found

- **You**: your own account is never part of the result.
- **Protected users**: administrators, users who can manage other users and super admins are shown in the preview as **Protected** and cannot be selected. Developers can change this with the [`ubdwp_is_protected_user`](./developers/hooks#ubdwp-is-protected-user) filter, and the Pro add-on adds [protection rules](/pro/protection).
- On **multisite**, users who are not members of the current site.

## Clear filters

**Clear filters** resets the form. The number next to each group shows how many of its filters are set.
