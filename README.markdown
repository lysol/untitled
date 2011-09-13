Untitled
========

This is a simple, no-frills Markdown-rendered personal wiki engine.

The wiki portion is merely a demo of a quick-n-dirty Sinatra/Web.py/Express-style
minimalist web framework. This is actually well-suited to PHP5 since it already
provides great interfaces to GET/POST, sessions, and cookies. It's easy to
forget with the current vogue of new frameworks and languages every week to
accomplish the same goals.

I also implemented a simple database model module that exposes database records
in an object-oriented fashion.

To properly implement, use the following .htaccess file and modify the
RewriteBase line to reflect the proper URL relative path.

<IfModule mod_rewrite.c>
RewriteEngine on
RewriteBase /~darnold/untitled/
RewriteCond %{REQUEST_FILENAME} |-f
RewriteCond %{REQUEST_FILENAME} |-d
RewriteRule . index.php [L]
</IfModule>