<?php

class Elavon
{
    private $request;
    private $response;

    public function send()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://remote.elavonpaymentgateway.com/remote");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "payandshop.com php version 0.9");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request);
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
        $this->response = curl_exec($ch);
        curl_close($ch);
    }

    public function createAuthRequest($array)
    {
        /* defaults (there is no default for 'url' or 'content') */

        /* for each argument set in the array, overwrite default */
        while (list($k, $v) = each($array)) {
            $$k = $v;
        }

        $timestamp = strftime("%Y%m%d%H%M%S");
        mt_srand((double)microtime() * 1000000);

        // creating the hash
        $tmp = "$timestamp.$merchantid.$orderid.$amount.$currency.$cardnumber";
        $md5hash = md5($tmp);
        $tmp = "$md5hash.$secret";
        $md5hash = md5($tmp);

        $str =
            "<request type='auth' timestamp='$timestamp'>
                <merchantid>$merchantid</merchantid>
                <orderid>$orderid</orderid>
                <account>$account</account>
                <amount currency='$currency'>$amount</amount>
                <card>
                    <number>$cardnumber</number>
                    <expdate>$expdate</expdate>
                    <type>$cardtype</type>
                    <chname>$cardname</chname>";
        if ($cvn) {
            $str .=
                "
                    <cvn>
                        <number>$cvn</number>
                        <presind>1</presind>
                    </cvn>";
        }
        $str .=
            "
                </card>
                <autosettle flag='$autosettleflag'/>
                <md5hash>$md5hash</md5hash>
                <tssinfo>
                    <address type='billing'>
                        <country>ie</country>
                    </address>
                </tssinfo>
            </request>";
        $this->request = $str;
        return $str;
    }

    public function parseAuthResponse()
    {

        $obj = new SimpleXMLElement($this->response);
        if ($obj->result != '00') {
            return ['success' => false, 'result' => (string)$obj->result, 'message' => (string)$obj->message];
        }
        return ['success' => true];
    }
}