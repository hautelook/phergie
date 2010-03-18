<?php
/**
 * Phergie
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://phergie.org/license
 *
 * @category  Phergie
 * @package   Tests
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

/**
 * Unit test suite for Pherge_Plugin_Handler
 *
 * @category Phergie
 * @package  Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Plugin_HandlerTest extends PHPUnit_Framework_TestCase
{
    protected $handler;

    /**
     * Sets up a new handler instance before each test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->handler = new Phergie_Plugin_Handler();
    }

    /**
     * Destroys the handler instance after each test
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->handler);
    }

    /**
     * Ensures that we can iterate over the handler
     *
     * @return void
     */
    public function testImplementsIterator()
    {
        $reflection = new ReflectionObject($this->handler);
        $this->assertTrue(
            $reflection->implementsInterface('IteratorAggregate')
        );

        $this->assertType(
            'Iterator', $this->handler->getIterator(),
            'getIterator() must actually return an Iterator'
        );
    }

    /**
     * Ensures a newly instantiated handler does not have plugins associated
     * with it
     *
     * @depends testImplementsIterator
     * @return void
     */
    public function testEmptyHandlerHasNoPlugins()
    {
        $count = 0;
        foreach ($this->handler as $plugin) {
            $count++;
        }

        $this->assertEquals(0, $count);
    }
    
    /**
     * Ensures a newly instantiated handler does not default to autoload
     *
     * @return void
     */
    public function testDefaultsToNotAutoload()
    {
        $this->assertFalse($this->handler->getAutoload());
    }

    /**
     * addPath provides a fluent interface
     *
     * @return void
     */
    public function testAddPathProvidesFluentInterface()
    {
        $handler = $this->handler->addPath(dirname(__FILE__));
        $this->assertSame($this->handler, $handler);
    }

    /**
     * addPath throws an exception when it cannot read the directory
     *
     * @return void
     */
    public function testAddPathThrowsExceptionOnUnreadableDirectory()
    {
        try {
            $this->handler->addPath('/an/unreadable/directory/path');
        } catch(Phergie_Plugin_Exception $e) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_DIRECTORY_NOT_READABLE,
                $e->getCode()
            );
            return;
        }

        $this->fail('An expected exception has not been raised.');
    }

    /**
     * adds a path into the plugin handler and then ensures that files
     * in that location can be found
     *
     * @return void
     */
    public function testAddPath()
    {
        $plugin_name = 'TestPluginFromFile';
        try {
            $this->handler->addPlugin($plugin_name);
        } catch(Phergie_Plugin_Exception $e) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_CLASS_NOT_FOUND,
                $e->getCode()
            );
            
            $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');

            try {
                $this->handler->addPlugin($plugin_name);
            } catch(Phergie_Plugin_Exception $e) {
                $this->fail(
                    'After adding the directory, the plugin was still'
                    . 'not found.'
                );
            }
            
            return;
        }

        $this->fail(
            'Before adding the directory, an expected exception'
            . 'was not raised'
        );
    }

    /**
     * Can add a plugin to the handler by shortname
     *
     * @todo refactor to use mock classes. note: addPlugin looks at the
     *       filesystem, so this may be problematic
     * @return void
     */
    public function testAddPluginToHandlerByShortname()
    {
        $returned_plugin = $this->handler->addPlugin('Ping');
        $this->assertTrue($this->handler->hasPlugin('Ping'));
        $this->assertTrue(
            $this->handler->getPlugin('Ping')
            instanceof Phergie_Plugin_Ping
        );
        $this->assertEquals(
            $this->handler->getPlugin('Ping'),
            $returned_plugin
        );
    }


    /**
     * Can add a plugin to the handler by instance
     *
     * @return void
     */
    public function testAddPluginToHandlerByInstance()
    {
        $plugin = $this->getMock('Phergie_Plugin_Abstract');
        $plugin
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('TestPlugin'));

        $returned_plugin = $this->handler->addPlugin($plugin);

        $this->assertTrue($this->handler->hasPlugin('TestPlugin'));
        $this->assertSame(
            $plugin, $returned_plugin,
            'addPlugin returns the same plugin'
        );
        $this->assertSame(
            $plugin, $this->handler->getPlugin('TestPlugin'),
            'getPlugin returns the same plugin'
        );
    }

    /**
     * addPlugin throws an exception when it can't find the plugin
     *
     * @return void
     */
    public function testAddPluginThrowsExceptionIfCannotFindPlugin()
    {
        try {
            $this->handler->addPlugin('TestPlugin');
        } catch(Phergie_Plugin_Exception $e) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_CLASS_NOT_FOUND,
                $e->getCode()
            );
            return;
        }

        $this->fail('An expected exception has not been raised.');
    }

    /**
     * implements __isset
     *
     * @return void
     */
    public function testPluginHandlerImplementsIsset()
    {
        $plugin_name = 'TestPlugin';

        $this->assertFalse(isset($this->handler->{$plugin_name}));

        $plugin = $this->getMock('Phergie_Plugin_Abstract');
        $plugin
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($plugin_name));

        $this->handler->addPlugin($plugin);

        $this->assertTrue(isset($this->handler->{$plugin_name}));

    }

    /**
     * addPlugin() returns the same plugin when requested twice
     *
     * @return void
     */
    public function testAddPluginReturnsSamePluginWhenAskedTwice()
    {
        $plugin1 = $this->handler->addPlugin('Ping');
        $plugin2 = $this->handler->addPlugin('Ping');
        $this->assertEquals($plugin1, $plugin2);
    }

    
    /**
     * Tests an exception is thrown when trying to get a plugin
     * that is not already loaded and autoload is off
     *
     * @depends testDefaultsToNotAutoload
     * @return void
     */
    public function testExceptionThrownWhenLoadingPluginWithoutAutoload()
    {
        try {
            $this->handler->getPlugin('Ping');
        } catch (Phergie_Plugin_Exception $expected) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_PLUGIN_NOT_LOADED,
                $expected->getCode()
            );
            return;
        }

        $this->fail('An expected exception has not been raised.');
    }
}
