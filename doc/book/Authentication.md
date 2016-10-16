# Authentication

## Pre Authentication
Simple Invoices now includes the `authenticate` event which is defined in `SimpleInvoices\Authentication\AutehenticationEvent::EVENT_AUTHENTICATE`.

Listening for this event allows you to extend the Authenticatio of Simple Invoices.

The event will give you access to the `Zend\Authentication\Adapter\AdapterInterface` object. Currently Simple Invoices uses an object extending `Zend\Authentication\Adapter\DbTable\CredentialTreatmentAdapter`, therefore you may access the `identity` and `credential` values and modify them before they are sent to the database server for validation. This should allow adding entropy to this fields, logging log in attempts, or whatever you wan to do with it.

Example: Merging username and password to avoid repeated password hashes if several users have the same password:

    $identity = $event->getAdapter()->getIdentity();
    $password = $event->getAdapter()->getCredential();
    $event->getAdapter()->setCredential($identity . '|' . $password);

## Post Authentication
We also supply a `authenticate.post` event defined in `SimpleInvoices\Authentication\AutehenticationEvent::EVENT_AUTHENTICATE`. This event triggers **after** the authentication has been performed and the result of the authentication in set in the parameter `authentication_result`. To get this variable simple execute:

    $authResult = $event->getParan('authentication_result');

This event is most appropiate for logging valid and/or invalid login attempts. It may also be usefull for blacklisting or blocking accounts on too many failed login attempts. All these features can be developed as an extension for Simple Invices just by listening to the events.