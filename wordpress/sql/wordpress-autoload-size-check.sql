/*
* WordPress Autoload Size Analysis
* 
* Purpose: Monitor and troubleshoot wp_options autoload performance
* Target: Keep total autoload under 1MB for optimal site performance
* 
* Usage:
* 1. Run Query 1 first to check total autoload size
* 2. If over 1MB, run Query 2 to identify the biggest offenders
* 
* What to do if over 1MB:
* - Look for plugin options that can be set to autoload='no'
* - Check for oversized transients or cache data
* - Consider cleaning up unused plugin options
* 
* Warning: Don't change core WordPress autoload options without research
*/

-- Query 1: Check total autoload size (target: under 1MB)
SELECT ROUND(SUM(LENGTH(option_value)) / (1024 * 1024), 2) AS total_autoload_mb
FROM wp_options
WHERE autoload = 'yes'
OR autoload = 'on';

-- Query 2: Find the largest autoloaded options (top 20)
SELECT option_name, 
       ROUND(LENGTH(option_value) / 1024, 2) AS value_size_kb
FROM wp_options
WHERE autoload = 'yes'
OR autoload = 'on'
ORDER BY value_size_kb DESC
LIMIT 20;