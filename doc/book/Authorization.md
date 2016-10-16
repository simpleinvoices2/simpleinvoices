# Authorization (ACL)
Simple Invoices now includes the `authorization` event which is defined in `SimpleInvoices\Mvc\MvcEvent::EVENT_AUTHORIZATION`.

Listening for this event allows you to extend the Authorization/ACL of Simple Invoices.

The listener method should return `true` if the user is **allowed** and `false` if the user is **not allowed** (AKA forbiden).

If `true` is received Simple invoices will continue its operations normally but if `false` is returned it will output a 403 error page and stop inmediatelly.

The ACL object may be obtained from the service manager of the application:
`$acl = $event->getApplication()->getServiceManager()->get('SimpleInvoices\Permission\Acl');`

The session data for authentication can be retrieved as follows:
`$sessionContainer = new \Zend\Session\Container('SI_AUTH');`

The module, view and action information is stored in the `SimpleInvoices\Mvc\Router\RouteMatch` object. As an example on how to fetch this information:

    $module = $event->getRouteMatch()->getParam('module');
    $view   = $event->getRouteMatch()->getParam('view');
    $action = $event->getRouteMatch()->getParam('action');
    
Other query parameters may be retrieved throug the `Request` object. For example, if we wish to retrieve the `id` query parameter:

    $id = $event->getRequest()->getQuery('id');
 