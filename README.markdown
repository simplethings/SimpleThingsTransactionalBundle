# SimpleThings TransactionalBundle

Wraps calls to controllers into a transaction, be it Doctrine DBAL or Persistence Managers (ORM, MongoDB, CouchDB).
Configuration is done via routing parameters or through a list of controllers/actions configured in the
extension config.

## How it works

For every Doctrine DBAL connection, every EntityManager and every DocumentManager the Transactional Bundle
creates a service that implements a transactions manager interface:

    interface TransactionManagerInterface
    {
        function beginTransaction();
        function commit();
        function rollBack();
    }

With the transactional bundle the following workflow is applied to a controller that is marked
as transactional (by default only if POST, PUT, DELETE request is found).

1. A transaction is started before the controller is called
2. The controller execution is wrapped in a try-catch block
3. On successful response generation (status code < 500) the transaction is committed. This includes a call to EntityManager::flush or DocumentManager::flush in case of an orm, mongodb or couchdb "transaction".
4. On status-code >= 500 the transaction is rolled back.
5. If an exception is thrown the transaction is rolled back.

Each transaction manager is named like the manager it belongs to:

    doctrine.orm.default_entity_manager => tx.orm.default
    doctrine_mongodb.odm.default_document_manager => tx.mongodb.default
    doctrine_couchdb.odm.default_document_manager => tx.couchdb.default

You can now mark your controllers as transactional by adjusting their route definitions:

    blog_post_edit:
        pattern: /blog/post/edit/{id}
        defaults: { _controller: "MyBlogBundle:Post:edit", _tx: "orm.default" }

Or if you need multiple transactional services:

    blog_post_edit:
        pattern: /blog/post/edit/{id}
        defaults: { _controller: "MyBlogBundle:Post:edit", _tx: ["orm.default", "mongodb.test"] }

If you want a GET request to be transactional you can explicitly set the methods which should perform a transactional:

    blog_post_edit:
        pattern: /blog/post/edit/{id}
        defaults: { _controller: "MyBlogBundle:Post:edit", _tx: "orm.default", _tx_methods: ["GET", "POST"] }

## Installation

1. Add TransactionalBundle to bin/deps:

    TransactionalBundle origin/HEAD

2. Add TransactionBundle to bin/2.0.0NEXT.deps:

    /bundles/SimpleThings   TransactionalBundle   http://github.com/SimpleThings/TransactionalBundle.git

3. Run ./bin/vendors.php to upgrade vendors and include TransactionalBundle

4. Add Bundle to app/AppKernel.php

    public function registerBundles() 
    {
        $bundles = array(
            //..
            new SimpleThings\TransactionalBundle\SimpleThingsTransactionalBundle(),
            //..
        );
    }

5. Add to autoload.php

    'SimpleThings'     => __DIR__.'/../vendor/bundles',

6. Configure extension:

    simple_things_transactional: ~

## FrameworkExtraBundle Integration

Since TransactionalBundle only uses the default attributes of a route to mark a controller transactional you
can configure this using annotations:

    use Sensio\Bundle\FrameworkExtraBundle\Configuration AS Extra;

    class PostController
    {
        /** @Extra\Route(pattern="/blog/post/edit/{id}", defaults={"_tx": "orm.default"})
        public function editAction($id)
        {

        }
    }

You can also mark the complete controller transactional:

    use Sensio\Bundle\FrameworkExtraBundle\Configuration AS Extra;

    /**
     * @ExtraRoute(defaults={"_tx": "orm.default"})
     */
    class PostController
    {

    }

## TODOS

* Make default tx methods configurable in extension.
* Add "auto_commit" configuration where you can specify a tx manager and it is ALWAYS wrapped around every master controller.