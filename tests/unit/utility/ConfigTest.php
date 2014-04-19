<?php

/**
* ownCloud - News
*
* @author Alessandro Cosentino
* @author Bernhard Posselt
* @copyright 2012 Alessandro Cosentino cosenal@gmail.com
* @copyright 2012 Bernhard Posselt dev@bernhard-posselt.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCA\News\Utility;

require_once(__DIR__ . "/../../classloader.php");


class ConfigFetcherTest extends \PHPUnit_Framework_TestCase {

	private $fileSystem;
	private $config;
	private $configPath;

	public function setUp() {
		$this->logger = $this->getMockBuilder(
			'\OCA\News\Core\Logger')
			->disableOriginalConstructor()
			->getMock();
		$this->fileSystem = $this->getMock('FileSystem', array(
			'file_get_contents',
			'file_put_contents',
			'file_exists'
		));
		$this->config = new Config($this->fileSystem, $this->logger);
		$this->configPath = 'config.json';
	}


	public function testDefaults() {
		$this->assertEquals(60, $this->config->getAutoPurgeMinimumInterval());
		$this->assertEquals(200, $this->config->getAutoPurgeCount());
		$this->assertEquals(30*60, $this->config->getSimplePieCacheDuration());
		$this->assertEquals(60, $this->config->getFeedFetcherTimeout());
		$this->assertEquals(true, $this->config->getUseCronUpdates());
		$this->assertEquals(8080, $this->config->getProxyPort());
		$this->assertEquals('', $this->config->getProxyHost());
		$this->assertEquals(null, $this->config->getProxyAuth());
		$this->assertEquals('', $this->config->getProxyUser());
		$this->assertEquals('', $this->config->getProxyPassword());
	}


	public function testRead () {
		$this->fileSystem->expects($this->once())
			->method('file_get_contents')
			->with($this->equalTo($this->configPath))
			->will($this->returnValue("autoPurgeCount = 3\nuseCronUpdates = true"));

		$this->config->read($this->configPath);

		$this->assertTrue(3 === $this->config->getAutoPurgeCount());
		$this->assertTrue(true === $this->config->getUseCronUpdates());
	}


	public function testReadBool () {
		$this->fileSystem->expects($this->once())
			->method('file_get_contents')
			->with($this->equalTo($this->configPath))
			->will($this->returnValue("autoPurgeCount = 3\nuseCronUpdates = false"));

		$this->config->read($this->configPath);

		$this->assertTrue(3 === $this->config->getAutoPurgeCount());
		$this->assertTrue(false === $this->config->getUseCronUpdates());
	}


	public function testReadLogsInvalidValue() {
		$this->fileSystem->expects($this->once())
			->method('file_get_contents')
			->with($this->equalTo($this->configPath))
			->will($this->returnValue('autoPurgeCounts = 3'));
		$this->logger->expects($this->once())
			->method('log')
			->with($this->equalTo('Configuration value "autoPurgeCounts" ' . 
				'does not exist. Ignored value.'), 
				$this->equalTo('warn'));

		$this->config->read($this->configPath);
	}


	public function testReadLogsInvalidINI() {
		$this->fileSystem->expects($this->once())
			->method('file_get_contents')
			->with($this->equalTo($this->configPath))
			->will($this->returnValue(''));
		$this->logger->expects($this->once())
			->method('log')
			->with($this->equalTo('Configuration invalid. Ignoring values.'), 
				$this->equalTo('warn'));

		$this->config->read($this->configPath);
	}


	public function testWrite () {
		$json = "autoPurgeMinimumInterval = 60\n" . 
			"autoPurgeCount = 3\n" . 
			"simplePieCacheDuration = 1800\n" . 
			"feedFetcherTimeout = 60\n" . 
			"useCronUpdates = true\n" .
			"proxyHost = yo man\n" .
			"proxyPort = 12\n" .
			"proxyUser = this is a test\n".
			"proxyPassword = se";
		$this->config->setAutoPurgeCount(3);
		$this->config->setProxyHost("yo man");
		$this->config->setProxyPort(12);
		$this->config->setProxyUser("this is a test");
		$this->config->setProxyPassword("se");

		$this->fileSystem->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo($this->configPath),
				$this->equalTo($json));

		$this->config->write($this->configPath);
	}


	public function testNoProxyAuthReturnsNull() {
		$this->assertNull($this->config->getProxyAuth());
	}


	public function testReadingNonExistentConfigWillWriteDefaults() {
		$this->fileSystem->expects($this->once())
			->method('file_exists')
			->with($this->equalTo($this->configPath))
			->will($this->returnValue(false));
		
		$this->config->setUseCronUpdates(false);

		$json = "autoPurgeMinimumInterval = 60\n" . 
			"autoPurgeCount = 200\n" . 
			"simplePieCacheDuration = 1800\n" . 
			"feedFetcherTimeout = 60\n" . 
			"useCronUpdates = false\n" .
			"proxyHost = \n" .
			"proxyPort = 8080\n" .
			"proxyUser = \n" .
			"proxyPassword = ";

		$this->fileSystem->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo($this->configPath),
				$this->equalTo($json));

		$this->config->read($this->configPath, true);
	}


	public function testEncodesUserAndPasswordInHTTPBasicAuth() {
		$this->config->setProxyUser("this is a test");
		$this->config->setProxyPassword("se");

		$this->assertEquals('this is a test:se', $this->config->getProxyAuth());
	}
}