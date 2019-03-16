CodaPHP
=======================
[![Current Version](https://img.shields.io/github/release/danielstieber/codaphp.svg?style=flat-square)](https://github.com/danielstieber/codaphp/releases)
[![Coda API Version](https://img.shields.io/badge/Coda_API_version-0.1.1--beta-orange.svg?style=flat-square)](https://coda.io/developers/apis/v1beta1)

CodaPHP is a library that makes it easy to use data from [Coda](https://www.coda.io) 
docs your in web projects by using the [Coda API (Beta)](https://coda.io/developers/apis/v1beta1). 
Coda itself as well as the API is still in beta. Use on your own risk.

Easily use all available API calls with one library including
* List all documents
* Read data from tables, formulas and controls
* Add/modify rows
* and a lot more

## Quickstart
### Installation and basic usage
Install the library through [Composer](http://getcomposer.org/):
```bash
php composer.phar require danielstieber/coda-php
```
and add it to your project:
```PHP
require './vendor/autoload.php';
$coda = new CodaPHP\CodaPHP('<YOUR API TOKEN>');

// List all your docs
$result = $coda->listDocs();
var_dump($result);
```

### Handling table data
Let's assume you have the table 'Products' in your Coda doc:
#### Products
| Title   ⚑ | Price | Status      |
|-----------|-------|-------------|
| Goatmilk  | 14.90 | available ▼ |
| Goatmeat  | 38.90 | available ▼ |

```PHP
// Get the price of the goatmilk
$docId = $coda->getDocId('<YOUR DOC URL>');

// Lists only Products with status 'available' (currently only one filter allowed)
$availableProducts = $coda->listRows($docId, 'Products', ['query' => ['status' => 'available']]);

// Show value of one cell
$result = $coda->getRow($docId, 'Products', 'Goatmilk');
var_dump($result['values']['Price']);
// Will show you 'float(14.90)'

// Add the new product 'Goatcheese'
if($coda->insertRows($docId, 'Products', ['Title' => 'Goatcheese', 'Price' => 23.50, 'Status' => 'available'])) {
  echo 'Product added';
}

// Change the status of the product 'Goatmilk' to 'sold out'
if($coda->insertRows($docId, 'Products', ['Title' => 'Goatmilk', 'Status' => 'sold out'], ['Title'])) {
  echo 'Product updated';
}
```

## Overview
This is a personal side project. If you have any suggestions, find bugs or want to contribute, don't hesitate to contact me. You can use the [offical Coda community](https://community.coda.io/) to asks questions and reach out as well.

### Token
Generate your token in the Coda profile settings. *Notice: Everyone with this token has full access to all your docs!*

### Methods
The method names are inspired by the wording of the [official Coda API documentation](https://coda.io/developers/apis/v1beta1) and are listed below.

### Parameters
All parameters can be found in the [official Coda API documentation](https://coda.io/developers/apis/v1beta1). Just add an associative array with your parameters to selected functions. The parameter _useColumnNames_ is set true by default in all 'row' functions. I list the important ones below.

### Response
In case of success, responses are mostly untouched but converted to PHP arrays. Exception is `insertRow()` function, which provides a boolean true in case of success.
In case of an error, the response includes the statusCode and provided error message, also untouched and converted to an array.

## Documentation
```PHP
$coda = new CodaPHP('<YOUR API TOKEN>'); // Create instance of CodaPHP
```
### Docs
```PHP
$coda->getDocId('<YOUR DOC URL>'); // Get the id of a doc

$coda->listDocs(); // List all docs you have access to
$coda->listDocs(['query' => 'todo']);  // List docs filtered by searchquery 'todo'
$coda->getDoc('<DOC ID>'); // Get a specific doc
$coda->createDoc('My new doc'); // Create a new doc
$coda->createDoc('Copy of my old doc', '<DOC ID>'); // Copy a doc
```
### Folders and Sections
```PHP
$coda->listSections('<DOC ID>'); // List all sections in a doc
$coda->getSection('<DOC ID>', '<SECTION NAME OR ID>'); // Get a section in a doc

$coda->listFolders('<DOC ID>'); // List all folders in a doc
$coda->getFolder('<DOC ID>', '<FOLDER NAME OR ID>'); //Get a folder in a doc
```
### Tables, Columns and Rows
```PHP
$coda->listTables('<DOC ID>'); // List all tables in a doc
$coda->getTable('<DOC ID>', '<TABLE NAME OR ID>'); // Get a table in a doc

$coda->listColumns('<DOC ID>', '<TABLE NAME OR ID>'); // List all columns in a table
$coda->getColumn('<DOC ID>', '<TABLE NAME OR ID>', '<COLUMN NAME OR ID>'); // Get a column in a table

$coda->listRows('<DOC ID>', '<TABLE NAME OR ID>'); // List all row in a table
$coda->insertRows('<DOC ID>', '<TABLE NAME OR ID>', [['<COLUMN ID OR NAME>' => '<VALUE>']], ['<KEYCOLUMN>']); // Insert/updates a row in a table

// Examples:
$coda->insertRows('<DOC ID>', 'todos', ['title' => 'Shower']); // Adds one row to 'todo'
$coda->insertRows('<DOC ID>', 'todos', [['title' => 'Wash dishes'], ['title' => 'Clean car']]); // Adds two rows to 'todo'
$coda->insertRows('<DOC ID>', 'todos', [['title' => 'Shower', 'status' => 'done'], ['title' => 'Buy goatcheese']], ['title']); // Updates the status of 'Shower' and inserts a new todo

$coda->updateRow('<DOC ID>', '<TABLE NAME OR ID>', '<ROW NAME OR ID>', ['<COLUMN ID OR NAME>' => '<VALUE>']); // List all rows in a table
$coda->getRow('<DOC ID>', '<TABLE NAME OR ID>', '<ROW NAME OR ID>'); // Get a row in a table
$coda->deleteRow('<DOC ID>', '<TABLE NAME OR ID>', '<ROW NAME OR ID>'); // Deletes a row in a table
```
### Formulas and Controls
```PHP
$coda->listFormulas('<DOC ID>'); // List all formulas in a doc
$coda->getFormula('<DOC ID>', '<FORMULA NAME OR ID>'); // Get a formula in a doc

$coda->listControls('<DOC ID>'); // List all controls in a doc
$coda->getControl('<DOC ID>', '<CONTROL NAME OR ID>'); //Get a control in a doc
```
### Account and other
```PHP
$coda->whoAmI(); // Get information about the current account
$coda->resolveLink('<DOC URL>'); // Resolves a link 
```

## Changelog
### 0.0.3 (March 16, 2019)
* Fixed an issue with using queries in listRows (Thanks to [Al Chen](https://github.com/albertc44) from Coda for mentioning this)
### 0.0.2 (November 15, 2018)
* Fixed an issue regarding table names with special characters (Thanks to Oleg from Coda for mentioning this)
### 0.0.1 (November 11, 2018)
* Initial version based on v0.1.1-beta of Coda API