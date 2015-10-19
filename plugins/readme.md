Plugins within a plugin
=======================

In this directory you can declare sub-plugins as classes that derive from abstract class FleximportPlugin. Each class represents a table that should be imported. The name of the class must match the name of the database-table with the data to be imported (case-sensitive).