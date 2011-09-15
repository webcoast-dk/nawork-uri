Quick manual for nawork_uri

1. General

n@work URI is also a url rewrite extension as RealURL or CoolURI. It was forked from CoolURI some day.

2. Requirements

PHP 5.2/5.3
MySQL 5
TYPO3 4.3

3. Installation & Configuration

3.1 Install the exention via the extension manager and apply the database changes

3.2 Extension configuraiton

The xml path holds the path to the configuration file, e.g.: fileadmin/resources/naworkUriConf.xml or fieladmin/config/uriConf.xml
Check the multidomain flag if you plan to use more than one domain in your installation

3.3 TypoScript configuration

Enter this in your typoscript:

config {
	tx_naworkuri {
		enable = 1
		redirect = 1
	}
}

"enable" activates the url transformation of nawork_uri
"redirect" activates the redirect mechanism if a page is called via the "index.php?id=10&..." form. The user is redirected to the correct path, if it exists

4. Help

If you need help setting up n@work URI, find bugs or have ideas to improve it, get in touch with me via email kapp@work.de