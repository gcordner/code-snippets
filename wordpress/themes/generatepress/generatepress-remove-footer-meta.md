# Remove GeneratePress Footer Meta (Tags, Categories, Author Info)

In GeneratePress, the blog/archive output includes tags, categories, and author info.  
These come from the `generate_after_entry_content` hook.

By default, the theme’s template runs:

```php
/**
 * generate_after_entry_content hook.
 *
 * @since 0.1
 *
 * @hooked generate_footer_meta - 10
 */
do_action( 'generate_after_entry_content' );
```

We can’t prevent the hook itself from firing without editing the template.  
But we *can* remove the callbacks attached to it.

---

## Step 1: Inspect the hook

Add this to your child theme’s `functions.php` to see what’s attached.  
The output goes to the PHP error log (or `wp-content/debug.log` if `WP_DEBUG_LOG` is enabled):

```php
add_action( 'wp', function() {
    if ( has_action( 'generate_after_entry_content' ) ) {
        global $wp_filter;
        error_log( print_r( $wp_filter['generate_after_entry_content'], true ) );
    } else {
        error_log( 'Nothing attached to generate_after_entry_content (at wp).' );
    }
}, 50 ); // run late
```

In a default GP setup, you’ll see:

- callback: `generate_footer_meta`  
- priority: `10`  
- accepted args: `1`  

---

## Step 2: Remove the callbacks

You have two options:

### Option A: Remove everything
```php
// functions.php (child theme)
add_action( 'wp', function() {
    remove_all_actions( 'generate_after_entry_content' );
}, 99); // run late
```

This nukes all callbacks on that hook. The downside: if GP or a plugin later adds something useful, it’ll get wiped out too.

---

### Option B: Remove just the footer meta
```php
// functions.php (child theme)
add_action( 'wp', function () {
    remove_action( 'generate_after_entry_content', 'generate_footer_meta', 10 );
}, 50);
```

This unhooks only `generate_footer_meta`. Any other callbacks on that hook will still run.

---

## Which is better?

The **surgical approach (Option B)** is recommended:

1. **Least collateral damage** – You only remove what you don’t want.  
2. **Future-proof** – New callbacks added later won’t be lost.  
3. **Clear intent** – Anyone reading your code sees exactly what’s disabled.

---

✅ **Use Option B for clean, maintainable code.**
