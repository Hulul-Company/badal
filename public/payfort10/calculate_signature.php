<?php
// generate_test_sig.php
// ملف مؤقت لتوليد signature للاختبار - امسحه بعد ما تخلص

$SHAResponsePhrase = '18rBnypfYP/04yelRkftp.$!';

// ✅ غير الـ merchant_reference للقيمة اللي رجعتلك من saveOrder
$params = [
    'merchant_reference' => '11907318106091',  // ← حط الـ order_identifier هنا
    'status'             => '14',
    'response_code'      => '14000',
    'amount'             => '10000',      // 100 ريال × 100 (PayFort بيبعت بالهللة)
    'currency'           => 'SAR',
    'fort_id'            => '999999999999999999',
    'payment_option'     => 'VISA',
    'customer_email'     => 'test@test.com',
    'command'            => 'PURCHASE',
    'response_message'   => 'Success',
    'merchant_identifier'=> 'reBWkbQY',
    'access_code'        => '8BvWIrSl2QmuMSeqC44m',
    'language'           => 'ar',
];

// حساب الـ Signature
ksort($params);
$shaString = '';
foreach ($params as $k => $v) {
    $shaString .= "$k=$v";
}
$shaString = $SHAResponsePhrase . $shaString . $SHAResponsePhrase;
$signature = hash('sha256', $shaString);

$params['signature'] = $signature;

echo "<h3>Webhook Test Data</h3>";
echo "<p><b>Signature:</b> $signature</p>";
echo "<hr>";
echo "<h4>Parameters to send in Postman (POST form-data):</h4>";
echo "<table border='1' cellpadding='8'>";
foreach ($params as $k => $v) {
    echo "<tr><td><b>$k</b></td><td>$v</td></tr>";
}
echo "</table>";

// JSON format
echo "<hr><h4>JSON:</h4>";
echo "<pre>" . json_encode($params, JSON_PRETTY_PRINT) . "</pre>";
