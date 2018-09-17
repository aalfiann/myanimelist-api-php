MyAnimeList API PHP Class
=======
[![Version](https://img.shields.io/badge/stable-1.0.1-green.svg)](https://github.com/aalfiann/myanimelist-api-php)
[![Total Downloads](https://poser.pugx.org/aalfiann/myanimelist-api-php/downloads)](https://packagist.org/packages/aalfiann/myanimelist-api-php)
[![License](https://poser.pugx.org/aalfiann/myanimelist-api-php/license)](https://github.com/aalfiann/myanimelist-api-php/blob/HEAD/LICENSE.md)

A simple MyAnimeList API class to access MyAnimeList to your website PHP.<br>
You can using official API from MyAnimeList or grabbing and we make it easy as you can using json to output the data.<br>
**Last tested on September 2018**.

Feature:
---

1. Search Anime or Manga using Title or ID
2. Data response is JSON, we don't use XML
3. Send request using proxy if needed

System Requirements
---

1. PHP 5.3 or newer

## Installation

Install this package via [Composer](https://getcomposer.org/).

1. For the first time project, you have to create the `composer.json` file, (skip to point 2, if you already have `composer.json`)  
```
composer init
```

2. Install
```
composer require "aalfiann/myanimelist-api-php:^1.0"
```

3. Done, for update in the future you can just run
```
composer update
```


# How to use Unofficial API (login is not required)
```php
<?php
require_once ('vendor/autoload.php');
use \aalfiann\MyAnimeList;

/**
 * Build object and set parameter
 * 
 * @property pretty = Output data json will be beautifier. Type data is boolean, default value is false. 
 * @property proxy = Create connection using proxy. Default value is null.
 * @property proxyauth = Your credentials to use the proxy. Default value is null.
 */
$getMAL = new MyAnimeList;
$getMAL->pretty = true;

/**
 * Example to search Anime 
 */
echo $getMAL->findAnime('overlord',true); //Set false to get data detail directly.

/**
 * Example to search Manga
 */
echo $getMAL->findManga('naruto',true); //Set false to get data detail directly.

/**
 * Example to get Anime by ID
 * Data will return 1, very accurate
 */
echo $getMAL->grabAnime('2886');

/**
 * Example to get Manga by ID
 * Data will return 1, very accurate
 */
echo $getMAL->grabManga('21');

?>
```

### How To Use Official API from MyAnimeList (login is required)
#### Note: Official API MyAnimeList hasbeen shutdown!

Be careful about your login, if you input wrong credential more than 10x, your ID and IP will get block for 8 hours.
Here is the example to call the data using Official MyAnimeList.

```php
<?php
require_once ('vendor/autoload.php');
use \aalfiann\MyAnimeList;

/**
 * Build object and set parameter
 * 
 * @property login = Your login in MyAnimeList. (Required)
 * @property pretty = Output data json will be beautifier. Type data is boolean, default value is false. 
 * @property proxy = Create connection using proxy. Default value is null.
 * @property proxyauth = Your credentials to use the proxy. Default value is null.
 */
$getMAL = new MyAnimeList;
$getMAL->login = 'yourusername:yourpassword';
$getMAL->pretty = true;

/**
 * Example to search Anime
 */
echo $getMAL->searchAnime('full metal',true); //Set false to get data detail directly.

/**
 * Example to search Manga
 */
echo $getMAL->searchManga('full metal',true); //Set false to get data detail directly.

/**
 * Example to get Anime
 * Data will return 1, accuracy will depend on the title
 */
echo $getMAL->searchAnime('full metal');

/**
 * Example to get Manga
 * Data will return 1, accuracy will depend on the title
 */
echo $getMAL->searchManga('full metal');

/**
 * Example to verify credentials in MyAnimeList
 */
echo $getMAL->verify();

?>
```

Limitation:
---
- This script is only to get data **Anime** and **Manga**.

So we need someone who have time to improve this.


How to Contribute
---
### Pull Requests

1. Fork the myanimelist-api-php repository
2. Create a new branch for each feature or improvement
3. Send a pull request from each feature branch to the develop branch