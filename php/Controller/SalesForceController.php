<?php

namespace Clifton\ClothesBuilderBundle\Controller;

use Clifton\ClothesBuilderBundle\Entity\Delivery;
use Clifton\ClothesBuilderBundle\Entity\DeliveryAddress;
use Clifton\ClothesBuilderBundle\Entity\Orders;
use Phpforce\SoapClient\Client;
use Phpforce\SoapClient\Soap\SoapClient;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;
use Clifton\ClothesBuilderBundle\Entity\Contact as ContactUs;
use Clifton\ClothesBuilderBundle\Entity\Request as CallBack;

class SalesForceController extends Controller
{
    protected $container;
    /** @var Client $soapClient */
    protected $soapClient;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->soapClient = $this->container->get('phpforce.soap_client');
    }

    /**
     * @param User $user
     * @return array
     */
    public function getAccountById($user)
    {
        $this->soapClient = $this->container->get('phpforce.soap_client');
        $sfObj = 'Account';
        $strColumnAccount = $this->makeupSObjects2SelectFields(array($sfObj));
        try {
            $resultAccountObj = $this->soapClient->query("select $strColumnAccount from Account");
            $result = $resultAccountObj->getQueryResult()->getRecords();
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    public function createAccount($firstName, $lastName, $phone)
    {
        $records[0] = new \stdClass();
        $records[0]->Name = $firstName . ' ' . $lastName;
        $records[0]->Phone = $phone;
        try {
            $result = $this->soapClient->create($records, 'Account');
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    /**
     * @param User $user
     * @return array
     */
    public function addAccount($user)
    {
        $records[0] = new \stdClass();
        $records[0]->Name = $user->getFirstname() . ' ' . $user->getLastname();
        $records[0]->Phone = $user->getPhone();
        try {
            $result = $this->soapClient->create($records, 'Account');
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    /**
     * @param User $user
     * @return array
     */
    public function updateAccount($user)
    {
        $records[0] = new \stdClass();
        $records[0]->Id = $user->getSFCode();
        $records[0]->Name = $user->getFirstname() . ' ' . $user->getLastname();
        $records[0]->Phone = $user->getPhone();
        $records[0]->ShippingStreet = $user->getAddress1() . ' ' . $user->getAddress2();
        $records[0]->ShippingCity = $user->getTown();
        $records[0]->ShippingCountry = $user->getCountry();
        $records[0]->ShippingPostalCode = $user->getPostCode();
        $records[0]->ShippingState = $user->getArea();
        try {
            $result[] = $this->soapClient->update($records, 'Account');
            $contact_id = $this->getContactByAccountId($user);
            $contact_id = $contact_id[0]->getId();
            $result[] = $this->updateContact($user, $contact_id);
        } catch (\Exception $ex) {
            $result[] = $ex->getMessage();
        }
        return $result;
    }

//    public function getLeadsAction()
//    {
//        $this->soapClient = $this->container->get('phpforce.soap_client');
//        $sfObj = 'Lead';
//        $strColumnAccount = $this->makeupSObjects2SelectFields(array($sfObj));
//        try {
//            $resultAccountObj = $this->soapClient->query("select $strColumnAccount from $sfObj");
//            $result = $resultAccountObj->getQueryResult()->getRecords();
//        } catch (\Exception $ex) {
//            $result = $ex->getMessage();
//        }
//        return $this->render('CliftonClothesBuilderBundle:Ajax:sf.html.twig', array('result' => $result));
//    }

    public function createLead($firstName, $lastName, $message, $email, $phone, $leadTipe = '', $url='', $file = null)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $records[0] = new \stdClass();
        $records[0]->FirstName = $firstName;
        $records[0]->LastName = $lastName;
        $records[0]->Your_message__c = $message;
        $records[0]->Email = $email;
        $records[0]->Phone = $phone;
        $records[0]->Company = $firstName . ' ' . $lastName;
        $records[0]->Lead_type__c = $leadTipe;
        $records[0]->Page__c = $url;
        $records[0]->OwnerId = '005b0000000bhLD';
        if ($file != null) {
            $records[0]->Lead_file__c = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . '/img/client_files/' . $file;
        }
        try {
            $result = $this->soapClient->create($records, 'Lead');
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    public function createLeadOnEnquiry($firstName, $lastName, $email, $phone, $message, $file = null, $user_id, $admin_url, $is_newsletter)
    {
        $records[0] = new \stdClass();
        $records[0]->FirstName = $firstName;
        $records[0]->LastName = $lastName;
        $records[0]->Email = $email;
        $records[0]->Phone = $phone;
        $records[0]->Your_message__c = $message;
        $records[0]->Company = $firstName . ' ' . $lastName;
        $records[0]->Lead_type__c = 'Enquiry';
        $records[0]->Site_User_ID__c = $user_id;
        $records[0]->Enquiry_URL__c = $admin_url;
        $records[0]->Newsletter_Opt_In__c = $is_newsletter;
        $records[0]->OwnerId = '005b0000000bhLD';
        if ($file != null) {
            $request = $this->container->get('request_stack')->getCurrentRequest();
            $records[0]->Lead_file__c = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . '/img/client_files/' . $file;
        }
        try {
            $result = $this->soapClient->create($records, 'Lead');
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    /**
     * @param ContactUs $contactUs
     * @return array
     */
    public function addLeadOnContactUs($contactUs, $file)
    {
        $records[0] = new \stdClass();
        $records[0]->FirstName = $contactUs->getFirstName();
        if (strlen($contactUs->getSurname()) == 0) {
            $records[0]->LastName = "Empty Name";
        } else {
            $records[0]->LastName = $contactUs->getSurname();
        }
        $records[0]->Email = $contactUs->getEmail();
        $records[0]->Your_message__c = $contactUs->getMessage();
        $records[0]->Phone = $contactUs->getPhone();
        $records[0]->Company = $contactUs->getFirstName() . ' ' . $contactUs->getSurname();
        $records[0]->Lead_type__c = 'Contact Us Request';
        $records[0]->OwnerId = '005b0000000bhLD';
        if ($file !== false) {
            $records[0]->Lead_file__c = $file;
        }
        $records[0]->Page__c = $contactUs->getUrl();
        try {
            $result = $this->soapClient->create($records, 'Lead');
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    /**
     * @param CallBack $callBack
     * @return array
     */
    public function addLeadOnRequest($callBack)
    {
        $request = $this->getRequest();
        $records[0] = new \stdClass();
        $records[0]->FirstName = $callBack->getFirstName();
        if (strlen($callBack->getSurname()) == 0) {
            $records[0]->LastName = "Empty Name";
        } else {
            $records[0]->LastName = $callBack->getSurname();
        }
        $records[0]->Your_message__c = $callBack->getText();
        $records[0]->Phone = $callBack->getPhone();
        $records[0]->Company = $callBack->getFirstName() . ' ' . $callBack->getSurname();
        $records[0]->Lead_type__c = 'Call Back Request';
        $records[0]->Page__c = $callBack->getUrl();
        $records[0]->OwnerId = '005b0000000bhLD';
        if ($callBack->getPath() != null) {
            $records[0]->Lead_file__c = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . '/img/client_files/' . $callBack->getPath();
        }
        try {
            $result = $this->soapClient->create($records, 'Lead');
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

//    public function updateLeadAction(Request $request)
//    {
//        $this->soapClient = $this->container->get('phpforce.soap_client');
//        $records[0] = new \stdClass();
//        $records[0]->Id = '00Q24000004dHycEAE';
//        $records[0]->Status = 'Working - Contacted';
//        try {
//            $result = $this->soapClient->update($records, 'Lead');
//        } catch (\Exception $ex) {
//            $result = $ex->getMessage();
//        }
//        return $this->render('CliftonClothesBuilderBundle:Ajax:sf.html.twig', array('result' => $result));
//    }

    /**
     * @param User $user
     * @return array
     */
    public function getContactByAccountId($user)
    {
        try {
            $resultAccountObj = $this->soapClient->query("select Id from Contact where AccountId in ('" . $user->getSFCode() . "')");
            $result = $resultAccountObj->getQueryResult()->getRecords();
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    public function getContactByEmail($email)
    {
        try {
            $resultAccountObj = $this->soapClient->query("select AccountId from Contact where Email in ('" . $email . "')");
            $result = $resultAccountObj->getQueryResult()->getRecords();
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    public function createContact($accountId, $firstName, $lastName, $email, $phone)
    {
        $records[0] = new \stdClass();
        $records[0]->AccountId = $accountId;
        $records[0]->FirstName = $firstName;
        $records[0]->LastName = $lastName;
        $records[0]->Email = $email;
        $records[0]->Phone = $phone;
        try {
            $result = $this->soapClient->create($records, 'Contact');
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    /**
     * @param User $user
     * @return array
     */
    public function addContact($user)
    {
        $records[0] = new \stdClass();
        $records[0]->AccountId = $user->getSFCode();
        $records[0]->FirstName = $user->getFirstname();
        $records[0]->LastName = $user->getLastname();
        $records[0]->Email = $user->getEmail();
        $records[0]->Phone = $user->getPhone();
        try {
            $result = $this->soapClient->create($records, 'Contact');
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    /**
     * @param User $user
     * @return array
     */
    public function updateContact($user, $id)
    {
        $records[0] = new \stdClass();
        $records[0]->Id = $id;
        $records[0]->AccountId = $user->getSFCode();
        $records[0]->FirstName = $user->getFirstname();
        $records[0]->LastName = $user->getLastname();
        $records[0]->Email = $user->getEmail();
        $records[0]->Phone = $user->getPhone();
        try {
            $result = $this->soapClient->update($records, 'Contact');
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    /**
     * @param Orders $order
     * @param string $accountId
     * @param $user_id
     * @param $admin_url
     * @param $sizing_url
     * @return array
     */
    public function addOpportunity($order, $accountId, $user_id, $admin_url, $sizing_url)
    {
        $records[0] = new \stdClass();
        $records[0]->AccountId = $accountId;
        $records[0]->Name = $order->getOnlineId();
        $records[0]->StageName = 'Won - Pre-Production';
        $records[0]->Sub_Stage_Status__c = 'Online Order Received';
        $records[0]->Customer_Notes__c = $order->getNotes();
        $date = Date('Y-m-d', strtotime("+7 days"));
        $records[0]->CloseDate = $date;
        $records[0]->Remarketing_Date__c = $date;
        $records[0]->Customer_approval_name__c = $order->getFirstname() . ' ' . $order->getSurname();
        $records[0]->Order_ID__c = (string)$order->getId();

        /** @var DeliveryAddress $deliveryAddress */
        $deliveryAddress = $order->getDeliveryAddressId();

        $records[0]->Opp_Delivery_Address_1__c = $deliveryAddress->getFirstAddress();
        $records[0]->Opp_Delivery_Address_2__c = $deliveryAddress->getSecondAddress();
        $records[0]->Opp_Delivery_Address_City__c = $deliveryAddress->getCity();
        $records[0]->Opp_Delivery_Address_Country__c = $deliveryAddress->getCountry();
        $records[0]->Opp_Delivery_Address_Postcode__c = $deliveryAddress->getPostcode();
        $records[0]->Opp_Delivery_Address_State_Privince__c = $deliveryAddress->getArea();

        $records[0]->Shipping_Street__c = $deliveryAddress->getFirstAddress() . ' ' . $deliveryAddress->getSecondAddress();
        $records[0]->Shipping_City__c = $deliveryAddress->getCity();
        $records[0]->Shipping_Country__c = $deliveryAddress->getCountry();
        $records[0]->Shipping_Post_Code__c = $deliveryAddress->getPostcode();
        $records[0]->Shipping_County__c = $deliveryAddress->getArea();
        $records[0]->Shipping_Phone_Number__c = $order->getPhone();

        $records[0]->Opp_Billing_Address_1__c = $order->getAddress();
        $records[0]->Opp_Billing_Address_2__c = $deliveryAddress->getSecondAddress();
        $records[0]->Opp_Billing_Address_City__c = $deliveryAddress->getCity();
        $records[0]->Opp_Billing_Address_Country__c = $deliveryAddress->getCountry();
        $records[0]->Opp_Billing_Address_Postcode__c = $deliveryAddress->getPostcode();
        $records[0]->Opp_Billing_Address_State_Privince__c = $deliveryAddress->getArea();

        $records[0]->Site_User_ID__c = $user_id;
        $records[0]->Opp_URL__c = $admin_url;
        $records[0]->Elavon_Transaction_ID__c = $order->getElavonOrderId();
        $records[0]->Sizing_Info__c = $sizing_url;

        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($order->getPath() != null) {
            $records[0]->uploaded_file__c = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . '/img/client_files/' . $order->getPath();
        }

        $records[0]->Amount = $order->getPrice();
        $records[0]->is_payed__c = 'Yes';
        $records[0]->PDF_Invoice__c = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . '/invoices/Invoice_' . $order->getId() . '.pdf';

        try {
            $result = $this->soapClient->create($records, 'Opportunity');
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }
    
    /**
     * @param Orders $order
     * @return array
     */
    public function updateOpportunity($order, $is_payed_only)
    {
        $records[0] = new \stdClass();
        $records[0]->Id = $order->getSfOrderId();
        if ($is_payed_only) {
            $records[0]->is_payed__c = 'Yes';
        }
        else {
            $records[0]->Customer_Notes__c = $order->getNotes();
            $records[0]->Customer_approval_name__c = $order->getFirstname() . ' ' . $order->getSurname();
            
            $deliveryAddress = $order->getDeliveryAddressId();

            $records[0]->Opp_Delivery_Address_1__c = $deliveryAddress->getFirstAddress();
            $records[0]->Opp_Delivery_Address_2__c = $deliveryAddress->getSecondAddress();
            $records[0]->Opp_Delivery_Address_City__c = $deliveryAddress->getCity();
            $records[0]->Opp_Delivery_Address_Country__c = $deliveryAddress->getCountry();
            $records[0]->Opp_Delivery_Address_Postcode__c = $deliveryAddress->getPostcode();
            $records[0]->Opp_Delivery_Address_State_Privince__c = $deliveryAddress->getArea();

            $records[0]->Shipping_Street__c = $deliveryAddress->getFirstAddress() . ' ' . $deliveryAddress->getSecondAddress();
            $records[0]->Shipping_City__c = $deliveryAddress->getCity();
            $records[0]->Shipping_Country__c = $deliveryAddress->getCountry();
            $records[0]->Shipping_Post_Code__c = $deliveryAddress->getPostcode();
            $records[0]->Shipping_County__c = $deliveryAddress->getArea();

            $records[0]->Amount = $order->getPrice();
            $records[0]->is_payed__c = 'No';
        }
        
        try {
            $result = $this->soapClient->update($records, 'Opportunity');
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    /**
     * @param Orders $order
     * @return array
     */
    public function updateOpportunityOnUserCreated($order)
    {
        $records[0] = new \stdClass();
        $records[0]->Id = $order->getSfOrderId();

        $records[0]->Site_User_ID__c = $order->getOrdersToUser()->getId();

        try {
            $result = $this->soapClient->update($records, 'Opportunity');
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    /**
     * @param string $sf_order_id
     * @return array
     */
    public function getOpportunityById($sf_order_id)
    {
        $this->soapClient = $this->container->get('phpforce.soap_client');
        try {
            $resultAccountObj = $this->soapClient->query("select Opp_Status_for_Customer__c, Name, Opportunity_Code__c from Opportunity where Id in ('" . $sf_order_id ."')");
            $result = $resultAccountObj->getQueryResult()->getRecords();
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    /**
     * @param array $SObjects
     *
     * @return abstract
     */
    public function makeupSObjects2SelectFields($SObjects) {
        $str = '';
        $obj = $this->soapClient->describeSObjects($SObjects);
        $arr = $obj[0]->getFields()->toArray();
        foreach ($arr as $key => $value) {
            $str .= ($value == end($arr)) ? $value->getName() : $value->getName() . ', ';
        }
        return $str;
    }
}
