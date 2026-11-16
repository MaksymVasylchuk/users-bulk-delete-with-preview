# More filters

Pro adds filters to **Find users according to certain criteria**, more filters to **Find users using WooCommerce filters**, and new filter types. Every type of membership or course filter can leave out accounts registered recently.

## Account

- **Username**: contains, starts with, ends with, does not contain
- **Registered** more than / within the last N days (counted from today, unlike the fixed dates of User Registration Date)
- **No role on this site** (often left behind by removed plugins or migrations)
- On multisite: **Sites of the network** (member of this site only, member of at least N sites)
- Email domains

## Activity

- **Last login**: users who have not logged in for N days. Pro records logins from its activation; older logins can be [imported](./export-and-logins#last-logins).
- Number of posts (per post type) and comments; only unpublished posts

## Spam signs

Every checked sign must match.

- Disposable email addresses
- Random-looking names (a guess: always check the preview)
- Numbered username series (bot1001, bot1002, …)
- Email domains that cannot receive mail
- Links in the profile
- Only spam comments
- Duplicate accounts (same email)

## Fraud signals

- Accounts sharing an IP address
- Registration waves: many accounts in a few minutes

## Advanced

- **More user meta conditions**: several conditions, matching **all conditions** or **any condition**
- **Except users of** a saved filter ("NOT"), for example *inactive subscribers, except customers*
- **At most N users**: oldest registrations first, least recently active first, or newest first. Applied after all other filters; useful to clean up a large site step by step.
- **Or a list of users**: paste emails, logins or user IDs, or read a CSV or TXT file

## WooCommerce

In **Find users using WooCommerce filters**:

- **Purchases**: number of orders, total spent, last order, unpaid orders, billing country, payment method, coupons, product categories
- **WooCommerce Subscriptions**: status for N days, trials without a paid renewal, failed renewals, products
- **WooCommerce Memberships**: plans, status for N days, ended free trials, access without an order
- **Fraud signals**: failed payments (card testing), refunds, only free orders, shared billing phone or address

## New filter types

| Type | Filters |
|---|---|
| **Find users using membership filters** | Paid Memberships Pro and Restrict Content Pro: plans, status, ended N days ago, active members |
| **Find users using LearnDash filters** | Course progress, groups, completed, never started |
| **Find users using Tutor LMS filters** | Progress, orders, instructor applications, quizzes |
| **Find users using BuddyPress filters** | Spammers, empty profiles, no updates, friends or groups |
| **Find users using Ultimate Member filters** | Account status, last login |

Each type appears only when its plugin is active.
