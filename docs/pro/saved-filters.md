# Saved filters

Save the filters of the **Bulk Users Delete** page under a name and use them again, from the page, in a [scheduled cleanup](./scheduled-cleanups) or with [WP-CLI](./wp-cli).

## Save

1. Set up the filters on the Bulk Users Delete page.
2. Click **Save filters**, enter a **Name of the saved filter**, and **Save**.

The filter type and every field of the form are saved, including the filters of Pro.

## Load

Choose the filter in **Saved filters** and click **Load**. Check the fields, then click **Preview**.

## Delete

Choose the filter and click **Delete**. A filter used by a scheduled cleanup cannot be deleted until the cleanup uses another filter.

## Fresh dates

Dates such as "registered more than 90 days ago" or "not logged in for 180 days" are counted from the day the filter runs, so a saved filter always finds the users who match **today**. The fixed dates of **User Registration Date** stay fixed.
