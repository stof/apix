<?php
namespace Zenya\Api;

use Zenya\Api\Entity,
    Zenya\Api\Router,
    Zenya\Api\Entity\EntityInterface,
    Zenya\Api\Entity\EntityClosure;

class EntityClosureTest extends \PHPUnit_Framework_TestCase
{

    public $definition = null;

    protected $entity, $route;

    protected function setUp()
    {
        $this->definition = array('action'=>function($id, $optional=null){return func_get_args();}, 'method'=>'GET', 'redirect' => 'location' );

        $this->entity = new Entity\EntityClosure;
        $this->entity->append($this->definition);

        $routes = array('/:controller/:id/:optional' => array());
        $this->route = new Router($routes);
        $this->route->setMethod('GET');
    }

    protected function tearDown()
    {
        unset($this->entity);
        unset($this->route);
    }

    public function testAppend()
    {
        $entity = $this->entity->toArray();

        $this->assertTrue($entity['actions']['GET']['action'] instanceOf \Closure);
        $this->assertTrue(is_callable($entity['actions']['GET']['action']));

        $this->assertSame('location', $entity['redirect'], "Check to see if parent::_append is called.");
    }

    public function testUnderlineCall()
    {
        $this->route->map('/controller/1234');

        $results = $this->entity->underlineCall($this->route);
        $this->assertSame(array('1234'), $results);
    }

    /**
     * @expectedException           \InvalidArgumentException
     * @expectedExceptionCode       405
     */
    public function testCallThrowsInvalidArgumentException()
    {
        $this->route->map('/controller/id');
        $this->route->setMethod('XXX');
        $this->entity->underlineCall($this->route);
    }

    /**
     * @expectedException           \BadMethodCallException
     * @expectedExceptionCode       400
     */
    public function testCallThrowsBadMethodCallException()
    {
        $this->route->map('/controller');
        $this->entity->underlineCall($this->route);
    }

    public function testParseDocsGroupLevel()
    {
        $this->entity->group("/* TODO {closure-group-title} */");
        $docs = $this->entity->_parseDocs();
        $this->assertSame("TODO {closure-group-title} ", $docs['title']);
        $this->assertSame(1, count($docs['methods']));
    }

    public function testGetMethod()
    {
        $method = $this->entity->getMethod($this->route);
        $this->assertInstanceOf('ReflectionFunction',  $method, "Shoulf be a ReflectionFunction instance");
        $this->assertSame('{closure}', $method->getShortName());
    }

    public function testGetActions()
    {
        $actions = $this->entity->getActions();

        // has the overide todo
        $this->assertSame(3, count($actions));
    }

    public function testAddRedirect()
    {
        $actions = $this->entity->redirect('paris');
        $entity = $this->entity->toArray();

        $this->assertSame('paris', $entity['redirect']);
    }


}
