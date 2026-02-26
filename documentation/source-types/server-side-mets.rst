Server-side METS
================

Sources of this type, will scan a directory recursively, searching for METS files.

Each METS file will be imported as an item. If it references other files (with
``fptr``) they will be imported as media.

Only ``dc`` metadata is currently supported.

Imported resources are not re-imported on the next runs. In other words, this
source type only creates resources. Resources are never updated.

Additional requirements
-----------------------

* `Local Media Ingester <https://omeka.org/s/modules/LocalMediaIngester/>`__ if
  the referenced files are local (on the Omeka server).

Configuration
-------------

Sources of this type have the following settings:

Path
    The absolute path of the directory containing METS files

Visibility of created resources
    Whether created resources should be made public or private

Process
-------

The import process starts by finding all METS files. Files are considered METS files if:

* their extension is ``.xml``,
* they are valid XML, and
* their root element is ``mets``

Then within each METS file we search for all ``/mets/structMap/div`` elements. For each ``div``:

#. Check if we already imported this item (the identifier used is ``/mets[@OBJID]``). If not:

   #. Find the corresponding ``/mets/dmdSec`` element
   #. Find a ``dc`` element inside this ``dmdSec`` element.
   #. Transform ``dc``'s children into Omeka literal values
   #. Set the item visibility depending on settings
   #. Attach the item to the default sites (sites with "Auto-assign new items" on)
   #. Save the item

#. Then we search for ``fptr`` elements within the ``div``. For each ``fptr`` found:

    #. Check if we already imported this media (the identifiers used are
       ``/mets[@OBJID]`` and ``fptr[@FILEID]``). If not:

        #. Find the corresponding ``/mets/fileSec//file`` and its ``FLocat``
           element. The ``href`` attribute is read to determine the file
           location. If it starts with ``http://`` or ``https://`` the file is
           downloaded first.
        #. If the module ``Alto`` is enabled and the file is an ALTO file, then
           the file is attached to the media corresponding to the previous
           ``fptr`` element.
