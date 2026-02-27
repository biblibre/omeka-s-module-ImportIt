Sources
=======

To be able to import resources, you need to declare a source. A source has a
name, a type and several settings depending on its type. The source type
dictates how resources are retrieved (whether it's from a file, a URL, or
something else) and transformed before being imported in Omeka.

You can have multiple sources of the same type.

Currently there is only one source type, :doc:`Server-Side METS
<source-types/server-side-mets>`, which browses local METS files and creates
corresponding items and media.

Create a source
---------------

To create a new source, click on "Import It" in the navigation menu.

.. image:: images/source-browse-empty.png

Then click on the "Add new source" button.

.. image:: images/source-add-form.png

Give the source a name and select its type, then click on the "Add" button.

.. image:: images/source-edit-server-side-mets.png

Fill the type-specific settings, then click on the "Save changes" button.

.. image:: images/source-browse-after-edit.png

For more details on type-specific settings, read the corresponding page in
:doc:`source-types`.

Start import
------------

Click on the import icon to go to the import settings form.

.. image:: images/source-import-form.png

Once the settings are done, click on the "Start import" button.
