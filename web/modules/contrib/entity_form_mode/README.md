INTRODUCTION
------------

This module lets you make use of form modes for default entities as nodes,
taxonomy terms or comments. It hooks up form modes corresponding to entity
form routes.
The route to add or edit an entity is called "entity.taxonomy_term.add" or
"entity.node.edit_form". All you have to do to display i.e. the edit form in
its own form mode is to offer a form mode with the (machine) name "edit_form"
(the last part of the route name). This is automatically selected by this
module to display the form.

The module is very lightweight. It consists of only 10 lines of code that make
the life of Drupal developers so much easier. There are no known issues.


## Restrictions:
Works for default entity types as Nodes, Taxonomy Terms, Comments and custom
entity types so far the forms are displayed with their own route and the naming
convention for entity forms is followed: entity.{entity_type_id}.{form_id}. For
adding Nodes you should use the "default" form mode, because the route name to
add nodes is "node.add" instead of "entity.node.add" for other node forms it
should work as expected. For User entities this module doesn't work.


INSTALLATION
------------

* Download and install as you usually do or [described here](
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).


CONFIGURATION
-------------

* Go to "Admin > Structure > Display Modes > Form Modes > Add Form Mode" (or
/admin/structure/display-modes/form/add) and select i.e. "Content".
* Type "Edit form" (important: Machine name must be "node.edit_form") and save.
* Got to "Admin > Structure > Content types > Article > Manage form display"
(or /admin/structure/types/manage/article/form-display) and enable the new form
mode "Edit form" in the section "Custom display settings" and save.
* Click the tab "Edit form" that appears now on the page "Manage form display"
and customize the Form to use, when you will edit an existing article entity.
* You are done.


REQUIREMENTS
------------

No requirements.



MAINTAINERS
-----------

Current maintainer:
 * [Joachim Feltkamp (JFeltkamp)][jfeltkamp]
