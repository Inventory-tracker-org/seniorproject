<?php
include_once "../../../config.php";
include_once "../../../kualiAPI/write/search.php";
include_once "../../../kualiAPI/dataworks-read/dw-check-forms.php";
$variables = [[]];
if (isset($_GET['dept_id'])) {
    $dept_id = $_GET['dept_id'];
    $audit_id = (int)$_GET['audit_id'];
    $get_curr_ids = "SELECT curr_self_id, curr_mgmt_id, curr_spa_id FROM audit_freq";
    $curr_stmt = $dbh->query($get_curr_ids);
    $curr_stmt->execute();
    $curr_results = $curr_stmt->fetch(PDO::FETCH_ASSOC);

    $select_audit = "SELECT unnest(check_forms) as check_forms FROM audit_history WHERE dept_id = :dept AND audit_id = :aid";
    $stmt = $dbh->prepare($select_audit);
    $stmt->execute([':dept' => $dept_id, ':aid' => $audit_id]);
    $audit_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$audit_info) {
        die('Empty Audit');
    }
    // GET TAGS
    $submit_audit = true;
    $transfer = $lsd = $in_progress = $done = false;
    foreach ($audit_info['check_forms'] as $index1 => $form) {
        $single_form = explode(',', $form);
        foreach ($single_form as $index => $single_array) {
            if ($index === 0) {
                $id = $single_array;
                continue;
            }

            if ($single_array === 'transfer') {
                if ($single_form[$index + 1] !== 'denied') {
                    $transfer = true;
                    $app_id = '68c73600df46a3027d2bd386';
                    continue;
                } else {
                    break;
                }
            }
            if ($single_array === 'lsd') {
                if ($single_form[$index + 1] !== 'denied') {
                    $lsd = true;
                    $app_id = '68d09e41d599f1028a9b9457';
                    continue;
                } else {
                    break;
                }
            }
            if ($single_array === 'lsd-cust') {
                if ($single_form[$index + 1] !== 'denied') {
                    $lsd = true;
                    $app_id = '68e94e8a58fd2e028d5ec88f';

                    continue;
                } else {
                    break;
                }
            }
            if ($single_array === 'in-progress') {
                $in_progress = true;
                continue;
            }
            if ($in_progress) {
                $done = checkForm($id, $single_array, $app_id, $form); // WAS ($id, $single_array, $app_id) EXPECTED 4 ARGUMENTS ERROR
            }
            if (!$done) {
                $submit_audit = false;
                break;
            }

            if ($index >= 3) {
                if ($transfer) {
                    $variables['data']['HgIvQwEnwb']['data'][$index1]['data']['xVdCwxjKl-'][] = $single_array;
                    $variables['data']['HgIvQwEnwb']['data'][$index1]['data']['2KqtRaCah1'][] = 'Transfer update after auditing';
                } else {
                    $variables['data']['g3eXi7dYR2']['data'][$index1]['data']['vJyySSnsqZ'][] = $single_array;
                }
            }
        }
        $transfer = $lsd = false;
    }
    if (!$submit_audit) {
        die('Forms are not done');
    }

    $subdomain = "csub";
    // SUBMITTER INFO
    $select = "SELECT kuali_key, f_name, l_name, school_id, signature, form_id, username FROM user_table WHERE email = :email";
    $email = $_SESSION['email'];
    $select_stmt = $dbh->prepare($select);
    $select_stmt->execute([":email" => $_SESSION['email']]);
    $submitter_info = $select_stmt->fetch(PDO::FETCH_ASSOC);
    $apikey = $submitter_info['kuali_key'] ?? '';
    if (empty($apikey)) {
        die("API Key Not Found");
    }
    $display_name = $submitter_info['username'];
    $full_name = $submitter_info['f_name'] . ' ' . $submitter_info['l_name'];
    if (empty($school_id) || empty($form_id)) {
        searchName($full_name, $apikey, $dept_id);
        $select_stmt = $dbh->prepare($select);
        $select_stmt->execute([":email" => $_SESSION['email']]);
        $submitter_info = $select_stmt->fetch(PDO::FETCH_ASSOC);
        $school_id = $submitter_info['school_id'] ?? '';
        $signature = $submitter_info['signature'] ?? $full_name;
        $form_id = $submitter_info['form_id'] ?? '';
    }
    $variables['E5WDwBqoR4'] = $submitter_info['f_name'] . ' ' . $submitter_info['l_name'];


    $get_dept_custodians = "SELECT dept_id, dept_name, unnest(custodian) as cust, dept_manager FROM department d WHERE dept_id = :dept_id";
    $get_cust_stmt = $dbh->prepare($get_dept_custodians);
    $get_cust_stmt->execute([":dept_id" => $dept_id]);
    $custodians = $get_cust_stmt->fetchAll(PDO::FETCH_ASSOC);
    $dept_name = $custodians[0]['dept_name'];

    $cust_count = count($custodians);
    $get_cust_info = "select email, form_id, school_id, username, f_name, l_name from user_table where CONCAT(f_name, ' ', l_name) = :full_name";
    $cust_name_split = explode(" ", $custodians[0]['cust']);
    try {
        $get_cust_stmt = $dbh->prepare($get_cust_info);
        $get_cust_stmt->execute([":full_name" => $custodians[0]['cust']]);
        $cust_info = $get_cust_stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($cust_info['form_id']) || empty($cust_info['school_id'])) {
            // SEARCH CUST IN KUALI
            searchName($custodians[0]['cust'], $apikey, $dept_id);
            $get_cust_stmt = $dbh->prepare($get_cust_info);
            $get_cust_stmt->execute([":full_name" => $custodians[0]['cust']]);
            $cust_info = $get_cust_stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // CUST DID NOT MATCH
        searchName($custodians[0]['cust'], $apikey, $dept_id);
        $get_cust_stmt = $dbh->prepare($get_cust_info);
        $get_cust_stmt->execute([":full_name" => $custodians[0]['cust']]);
        $cust_info = $get_cust_stmt->fetch(PDO::FETCH_ASSOC);
        // SEARCH CUST IN KUALI
    }
    $variables['data']['lHuAQy0tZd']['displayName'] = $custodians[0]['cust'];
    $variables['data']['lHuAQy0tZd']['email'] = $cust_info['email'];
    $variables['data']['lHuAQy0tZd']['firstName'] = $cust_info['f_name'];
    $variables['data']['lHuAQy0tZd']['id'] = $cust_info['form_id'];
    $variables['data']['lHuAQy0tZd']['label'] = $custodians[0]['cust'];
    $variables['data']['lHuAQy0tZd']['lastName'] = $cust_info['l_name'];
    $variables['data']['lHuAQy0tZd']['schoolId'] = $cust_info['school_id'];
    $variables['data']['lHuAQy0tZd']['username'] = $cust_info['username'];

    $manager_name = $custodians['dept_manager'][0];
    try {
        $get_mana_stmt = $dbh->prepare($get_cust_info);
        $get_mana_stmt->execute([":full_name" => $manager_name]);
        $mana_info = $get_mana_stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($mana_info['form_id']) || empty($mana_info['school_id'])) {
            // SEARCH CUST IN KUALI
            searchName($manager_name, $apikey, $dept_id);
            $get_mana_stmt = $dbh->prepare($get_cust_info);
            $get_mana_stmt->execute([":full_name" => $custodians[0]['cust']]);
            $mana_info = $get_mana_stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
    $variables['data']['55-0zfJWML']['displayName'] = $manager_name;
    $variables['data']['55-0zfJWML']['email'] = $mana_info['email'];
    $variables['data']['55-0zfJWML']['firstName'] = $mana_info['f_name'];
    $variables['data']['55-0zfJWML']['id'] = $mana_info['form_id'];
    $variables['data']['55-0zfJWML']['label'] = $manager_name;
    $variables['data']['55-0zfJWML']['lastName'] = $mana_info['l_name'];
    $variables['data']['55-0zfJWML']['schoolId'] = $mana_info['school_id'];
    $variables['data']['55-0zfJWML']['username'] = $mana_info['username'];

    if (isset($variables['data']['HgIvQwEnwb']['data'][$index1]['data']['xVdCwxjKl-'])) {
        $variables['data']['R0rMnJsvtQ']['id'] = 'yes';
        $variables['data']['R0rMnJsvtQ']['Label'] = 'Yes';
    } else {
        $variables['data']['R0rMnJsvtQ']['id'] = 'no';
        $variables['data']['R0rMnJsvtQ']['Label'] = 'No';
    }
    if (isset($variables['data']['g3eXi7dYR2']['data'][$index1]['data']['vJyySSnsqZ'])) {
        $variables['data']['3WfG7CrNND']['id'] = 'yes';
        $variables['data']['3WfG7CrNND']['Label'] = 'Yes';
    } else {
        $variables['data']['3WfG7CrNND']['id'] = 'no';
        $variables['data']['3WfG7CrNND']['Label'] = 'No';
    }
    if (!$apikey) {
        die("No API key found for user.");
    }

    $url = "https://{$subdomain}.kualibuild.com/app/api/v0/graphql";

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $headers = array(
        "Content-Type: application/json",
        "Authorization: Bearer {$apikey}",
    );
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $data = '{"query":"mutation ($appId: ID!) { initializeWorkflow(args: {id: $appId}) { actionId }}","variables":{
    "appId": "68e5ccf75911b5028c9e9d3e"
}}';

    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $resp = curl_exec($curl);

    $decoded_data = json_decode($resp, true);
    $action_id = $decoded_data['data']['initializeWorkflow']['actionId'];
    curl_close($curl);

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $get_draft_id = json_encode([
        'query' => 'query ($actionId: String!) { action(actionId: $actionId) { id appId document { id } } }',
        'variables' => [
            'actionId' => $action_id
        ]
    ]);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $get_draft_id);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($curl);
    var_dump($resp);
    $decoded_data = json_decode($resp, true);
    $document_id = $decoded_data['data']['action']['document']['id'];
    $action_id = $decoded_data['data']['action']['id'];

    curl_close($curl);


    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    if (!$action_id || !$document_id) {
        die("Missing required data.\nactionId: $action_id\ndocumentId: $document_id");
    }

    if (!$action_id) {
        die("ERROR: actionId is NULL before submitting the document.");
    }
    $now_array = new DateTime();
    $now_array->setTimezone(new DateTimeZone('America/Los_Angeles'));
    $now = $now_array->format('Y-m-d\TH:i:s.v\Z');

    $ms_time = round(microtime(true) * 1000);
    $variables['documentId'] = $document_id;
    $variables['actionId'] = $action_id;
    $variables['status'] = 'completed';
    $submit_form = json_encode([
        'query' => 'mutation ($documentId: ID!, $data: JSON, $actionId: ID!, $status: String)
    { submitDocument( id: $documentId data: $data actionId: $actionId status: $status )}',
        'variables' => $variables
    ]);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $submit_form);

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $resp = curl_exec($curl);
    $resp_data = json_decode($resp, true);

    curl_close($curl);
    echo json_encode([
        $document_id,
        $action_id,
        $form_id,
        $resp_data
    ]);
    ob_get_clean();
    exit;
}
header("Location: https://dataworks-7b7x.onrender.com/audit/audit-history/search-history.php?type=failure&reason=GET_NOT_SET");
exit;
