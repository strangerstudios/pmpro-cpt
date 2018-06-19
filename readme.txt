=== Paid Memberships Pro - Custom Post Type Add On ===
Contributors: strangerstudios
Tags: pmpro, paid memberships pro, members, custom post type, cpt, redirect
Requires at least: 3.5
Tested up to: 4.9.6
Stable tag: .2.1

Adds the 'Require Membership' meta box to all CPTs selected and redirects non-members to the selected page.

== Description ==

This plugin will add the PMPro "Require Membership" meta box to all CPTs selected. If a non-member visits that single CPT (either a logged out visitor or a logged in user without membership access) they will be redirected to the selected page.

== Settings == 
Navigate to Settings > PMPro CPTs in the WordPress Admin to select custom post types and set redirection rules.

== Changelog ==

= .2.1 =
* BUG FIX: Fixed redirect issue when no CPTs were selected on the settings page but the is_singular check was still returning true.
* ENHANCEMENT: WordPress Coding Standards and Improved PHPDoc Blocs

= .2 =
* BUG FIX: Fixed a warning when getting the redirect_to setting. (Thanks, Sarah Hines on GitHub)
* ENHANCEMENT: Added a pmprocpt_redirect_to filter that can be used to change what page the user is redirected to when accessing a restricted CPT they don't have the appropriate membership level for. See here for an example on how to use this filter: https://gist.github.com/strangerstudios/dd213f75c67935a447146ec430498c6d

= .1 =
* Initial commit.