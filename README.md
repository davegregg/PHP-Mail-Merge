PHP-Mail-Merge
==============

Dm/G PHP Mail Merge distributes *variable* email campaigns. If you need to send a single templated email to a large number of recipients, but you want each email to be slightly different (e.g. for branding purposes, or to include the recipient's name or a unique promotional code), then PHP Mail Merge can help you. 

1. Supply PHP Mail Merge with a template, containing unique variables in the places you need the varying content to go. 
2. Supply a comma-delimited list of these variables, so PHP Mail Merge knows what to look for in your template. 
3. Supply the list of recipient addresses, and the associated recipient-unique values to replace the variables in your template. 

PHP Mail Merge will parse through the template looking for each variable, replacing it with the value you supplied, and then send the final email to the address you supplied. Webversions of each email are generated for the recipient to view in their browser (if desired) and click-tracking functionality is built-in.

Tips
====
* Variables can be used not only in the body of the email, but in the subject line, sender's address, and reply-to address, which are especially handy for dynamic branding
* There are a few hardcoded variables you should know:
    * `___dmgmmURL` returns the URL of the webversion of the email (obviously, for providing a 'view-in-your-browser' copy of the email to recipients)
    * `___dmgmmTrackThis_` (note the trailing underscore) when prefixed to a URL in an anchor link, the link will redirect the user through the PHP of the webversion file which will track the click in the view log and then forward the user on to the destination URL
    * `___dmgmmRecipient` returns the recipient's email address (in case you want to repeat a recipient's email address to them
    * `___dmgmmSender` returns the sender's email address (handy for dynamic email signatures and the like)
    * `___dmgmmReplyTo` returns the reply-to email address (handy for unsubscribe mailto links and the like)
    * `___dmgmmSubject` returns the reply-to email address (handy for the HTML title tag in the webversion, for example)
    
TODOs
=====
* **Optimization for scaling:** the app works fine for a few thousand emails at a time. I've not had an occasion to test this with larger jobs.
* **Security review** (e.g. sanitization)
* **Refactoring:** This was written ~2010. I didn't observe a popular styleguide. And PHP itself has changed quite a bit since then anyway. So this code should be revised for new patterns and practices.
* **UI/UX update:** it's certainly not pretty. It was a quick solution for a technician.
* More documentation (e.g. for records and logs)
* Add a logging option
* Add memory-limit-error mitigation, by providing an option for the user to give a list of files containing address-values sets, instead of a list of sets directly into the form
* Strip whitespace and newlines from between address-values sets intelligently
* Add option to pull the template from a file
* More error handling
