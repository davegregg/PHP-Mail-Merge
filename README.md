PHP-Mail-Merge
==============

Dm/G PHP Mail Merge distributes *variable* email campaigns. If you need to send a single templated email to a large number of recipients, but you want each email to be slightly different (e.g. for branding purposes, or to include the recipient's name or a unique promotional code), then PHP Mail Merge can help you. 

1. Supply PHP Mail Merge with a template, containing unique variables in the places you need the varying content to go. 
2. Supply a comma-delimited list of these variables, so PHP Mail Merge knows what to look for in your template. 
3. Supply the list of recipient addresses, and the associated recipient-unique values to replace the variables in your template. 

PHP Mail Merge will parse through the template looking for each variable, replacing it with the value you supplied, and then send the final email to the address you supplied. Webversions of each email are generated for the recipient to view in their browser (if desired) and click-tracking functionality is built-in.
