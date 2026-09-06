<?php
namespace App\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use App\Models\BlacklistCase;
use App\Helpers\NepaliTextHelper;

class DocumentService
{
    /**
     * Dynamically generates the 45 Days Notice Document in Nepali (Unicode)
     */
    public function generateNotice(BlacklistCase $case, array $data)
    {
        $phpWord = new PhpWord();

        // ─── Font: 'Preeti' for Nepali text output ────────────────────
        // Text from DB is Unicode; NepaliTextHelper converts to Preeti ASCII
        // The Word document uses Preeti font so glyphs render correctly.
        $fontStyle    = ['name' => 'Preeti', 'size' => 12];
        $boldStyle    = ['name' => 'Preeti', 'size' => 12, 'bold' => true];
        $paragraphStyle = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH, 'spaceAfter' => 200];
        $rightAlign = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT];
        $centerAlign = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];

        $section = $phpWord->addSection();
        
        // Header Date, Time & Ref
        $dateString = $data['date'] ?? '................';
        $engDateString = !empty($data['eng_date']) ? ' (' . $data['eng_date'] . ')' : '';
        $timeString = !empty($data['time']) ? " समय: " . $data['time'] : '';
        $section->addText("मितिः " . $dateString . $engDateString . $timeString, $fontStyle, $rightAlign);
        $section->addText("पत्र संख्याः " . ($data['ref_number'] ?? '................'), $fontStyle);
        $section->addTextBreak(1);
        
        // Addressee Block — convert Unicode name from DB → Preeti for doc
        $customerNameUnicode = $case->target->name_nepali ?? $case->target->name_english;
        $customerName = NepaliTextHelper::unicodeToPreeti($customerNameUnicode);
        $customerAddr = NepaliTextHelper::unicodeToPreeti($data['customer_address'] ?? '................');
        $section->addText(NepaliTextHelper::unicodeToPreeti($customerNameUnicode), $boldStyle);
        $section->addText(NepaliTextHelper::unicodeToPreeti('ठेगानाः ') . $customerAddr, $fontStyle);
        $section->addTextBreak(1);
        
        // Subject
        $section->addText("विषयः चेक बापतको आवश्यक रकम जम्मा गर्ने सम्बन्धमा ४५ दिने सूचना ।", $boldStyle, $centerAlign);
        $section->addTextBreak(1);
        
        // Main Body Paragraph 1 (Dynamic Variable Injection)
        $body1 = "तपाईको यस नेपाल एसबिआई बैंकको {$data['branch_name']} शाखा कार्यालयमा रहेको खाता नं {$case->account_number} बाट भुक्तानी हुने गरीे चेक धारक {$data['payee_name']} को नाममा जारी गर्नुभएको रु. {$data['amount']} (अक्षरेपी {$data['amount_words']}) रकमको चेक नं {$data['cheque_number']} को चेकबाट भुक्तानी प्राप्त गर्न चेक धारक {$data['payee_name']} ले मिति {$data['presentation_date_1']}, मिति {$data['presentation_date_2']} मा पेश गर्नु भएकोमा उक्त खातामा मौज्दात नभएको / मौज्दात पर्याप्त नभएको कारणबाट भुक्तानी हुन नसकेको हुँदा चेक धारकले उक्त चेक अनादर भएको प्रमाणित गराउन यस बैंकमा मिति {$data['application_date']} मा निवेदन पेश गर्नुभएको हुँदा सो मितिले ४५ (पैँंतालिस) दिन भित्र खातामा आवश्यक रकम जम्मा गरी उक्त चेक बापतको रकम भुक्तानीका लागि आवश्यक व्यवस्था गर्नुहुन अनुरोध गर्दछौं ।";
        
        $section->addText($body1, $fontStyle, $paragraphStyle);
        
        // Main Body Paragraph 2
        $body2 = "उक्त समयावधिभित्र उल्लेखित चेक बापतको रकम खातामा जम्मा नभई भूक्तानी माग्दा चेक धारकलाई भुक्तानी दिन नसकिने भएमा नेपाल राष्ट्र बैंकबाट जारी गरिएको चेक अनादर गरेको व्यहोरा प्रमाणित गर्ने कार्यविधि, २०८२ बमोजिम यस बैंकले चेक अनादर भएकोे प्रमाणित गरीदिने व्यहोरा अनुरोध छ । साथै, नेपाल राष्ट्र बैंकबाट जारी निर्देशन बमोजिम चेक धारकले तपाई खातावालालाई कालोसूचीमा राख्न निवेदन दिएको अवस्थामा तपाईलाई कालोसूचीमा राख्न कर्जा सूचना केन्द्रमा लेखि पठाइने व्यहोरा यसै पत्र मार्फत् जानकारीको लागि अनुरोध छ ।";
        
        $section->addText($body2, $fontStyle, $paragraphStyle);
        $section->addTextBreak(2);
        
        // Signature Block
        $section->addText("शाखा प्रबन्धक", $boldStyle, $rightAlign);
        $section->addText("नेपाल एसबिआई बैंक लि.", $boldStyle, $rightAlign);
        $section->addText("{$data['branch_name']} शाखा", $boldStyle, $rightAlign);
        
        // Save to temporary storage
        $fileName = '45_Days_Notice_' . str_replace('-', '_', $case->case_number) . '.docx';
        $filePath = storage_path('app/private/' . $fileName);
        
        // Ensure directory exists
        if (!file_exists(storage_path('app/private/'))) {
            mkdir(storage_path('app/private/'), 0755, true);
        }
        
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($filePath);
        
        return $filePath;
    }

    /**
     * Dynamically generates the Cheque Dishonour Certificate Document in Nepali (Unicode)
     */
    public function generateDishonourCertificate(BlacklistCase $case, array $data)
    {
        $phpWord = new PhpWord();

        // ─── Font: 'Preeti' for Nepali text output ────────────────────
        $fontStyle    = ['name' => 'Preeti', 'size' => 12];
        $boldStyle    = ['name' => 'Preeti', 'size' => 12, 'bold' => true];
        $paragraphStyle = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH, 'spaceAfter' => 200];
        $rightAlign = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT];
        $centerAlign = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];

        $section = $phpWord->addSection();
        
        // Header Date, Time & Ref
        $dateString = $data['date'] ?? '................';
        $engDateString = !empty($data['eng_date']) ? ' (' . $data['eng_date'] . ')' : '';
        $timeString = !empty($data['time']) ? " समय: " . $data['time'] : '';
        $section->addText("मितिः " . $dateString . $engDateString . $timeString, $fontStyle, $rightAlign);
        $section->addText("पत्र संख्याः " . ($data['ref_number'] ?? '................'), $fontStyle);
        $section->addTextBreak(1);
        
        // Addressee Block
        $section->addText("नाम: " . ($data['payee_name'] ?? '................'), $boldStyle);
        $section->addText("ठेगानाः " . ($data['payee_address'] ?? '................'), $fontStyle);
        $section->addTextBreak(1);
        
        // Subject
        $section->addText("विषयः चेक अनादर भएको प्रमाणित गरिएको सम्बन्धमा ।", $boldStyle, $centerAlign);
        $section->addTextBreak(1);
        
        // Main Body Paragraph 1
        $body1 = "उपरोक्त सम्बन्धमा तपाईले यस बैंकमा मिति {$data['application_date']} मा चेक अनादर भएको प्रमाणित गराउन निवेदन पेश गरे बमोजिम चेक अनादर भएको व्यहोरा प्रमाणित गरिएको सम्बन्धमा यो पत्र लेखिदैछ ।";
        $section->addText($body1, $fontStyle, $paragraphStyle);
        
        // Main Body Paragraph 2 — convert Unicode names from DB to Preeti
        $customerNameUnicode = $case->target->name_nepali ?? $case->target->name_english;
        $customerName = NepaliTextHelper::unicodeToPreeti($customerNameUnicode);
        $reason = $data['dishonour_reason'] ?? 'खातामा मौज्दात नभएको वा मौज्दात पर्याप्त नभएको';
        $body2 = "यस बैंकका खातावाला {$customerName} ले यस बैंकको {$data['branch_name']} शाखामा खोलिएको खाता मार्फत भुक्तानी हुने गरी तपाई {$data['payee_name']} को नाममा तपसिलमा उल्लेखित रकम बराबरको चेक जारी गरिदिनुभएकोमा तपाईले उक्त चेकबाट भुक्तानी प्राप्त गर्न मिति {$data['presentation_date_1']}, मिति {$data['presentation_date_2']} मा चेक पेश गर्नुभएकोमा उक्त खातामा मौज्दात नभएको वा मौज्दात पर्याप्त नभएको / {$reason} कारण चेकबाट भुक्तानी हुन नसकेको हुँदा मिति {$data['notice_date']} मा निज खातावालालाई चेक बापतको आवश्यक रकम खातामा जम्मा गर्न ४५ (पँैतालीस) दिनको म्याद दिई सूचना दिईएको र सो बमोजिम उक्त खातामा आवश्यक मौज्दात रकम जम्मा नभएको कारण तपाईले भुक्तानी माग्दा भुक्तानी दिन नसकिएकोमा तपाईले मिति {$data['application_date']} मा चेक अनादर भएको प्रमाणित गरिदिनु हुन यस बैंकमा निवेदन पेश गरे बमोजिम तपसिलमा उल्लेखित चेक अनादर भएको प्रमाणित गरिन्छ ।";
        $section->addText($body2, $fontStyle, $paragraphStyle);
        $section->addTextBreak(1);
        
        // Table Details (तपसिल)
        $section->addText("तपसिल", $boldStyle, $centerAlign);
        
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $phpWord->addTableStyle('DishonourTable', $tableStyle);
        $table = $section->addTable('DishonourTable');
        
        $tableRows = [
            ['चेक नम्बर', $data['cheque_number'] ?? ''],
            ['चेक धारकको नाम', $data['payee_name'] ?? ''],
            ['चेक जारी गर्ने खातावालाको नाम', $customerName],
            ['भुक्तानी हुने रकम', $data['amount'] ?? ''],
            ['चेक जारी भएको मिति', $data['cheque_issue_date'] ?? '']
        ];
        
        foreach ($tableRows as $row) {
            $table->addRow();
            $table->addCell(4000)->addText($row[0], $boldStyle);
            $table->addCell(5000)->addText($row[1], $fontStyle);
        }
        
        $section->addTextBreak(2);
        
        // Signature Block
        $section->addText("शाखा प्रबन्धक", $boldStyle, $rightAlign);
        $section->addText("नेपाल एसबिआई बैंक लि.", $boldStyle, $rightAlign);
        $section->addText("{$data['branch_name']} शाखा", $boldStyle, $rightAlign);
        
        // Save to temporary storage
        $fileName = 'Dishonour_Certificate_' . str_replace('-', '_', $case->case_number) . '.docx';
        $filePath = storage_path('app/private/' . $fileName);
        
        if (!file_exists(storage_path('app/private/'))) {
            mkdir(storage_path('app/private/'), 0755, true);
        }
        
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($filePath);
        
        return $filePath;
    }

    /**
     * Dynamically generates the Cover Note Document in English
     */
    public function generateCoverNote(BlacklistCase $case, array $data)
    {
        $phpWord = new PhpWord();
        
        $fontStyle = ['name' => 'Calibri', 'size' => 11];
        $boldStyle = ['name' => 'Calibri', 'size' => 11, 'bold' => true];
        $boldUnderline = ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'underline' => 'single'];
        
        $section = $phpWord->addSection();
        
        $section->addText("Ref No: " . ($data['ref_number'] ?? '................'), $fontStyle);
        $section->addTextBreak(1);
        
        $section->addText("NOTE TO HEAD OF DEPARTMENT", $boldUnderline);
        $section->addText("CENTRAL OPERATION DEPARTMENT", $boldUnderline);
        $section->addText("NEPAL SBI BANK LIMITED", $boldUnderline);
        $section->addText("CORPORATE OFFICE, KAMLADI", $boldUnderline);
        $section->addTextBreak(1);
        
        $section->addText("THROUGH PROVINCE HEAD", $boldUnderline);
        $section->addText(strtoupper($data['province_name'] ?? '................') . " PROVINCE OFFICE", $boldUnderline);
        $section->addText("NEPAL SBI BANK LIMITED", $boldUnderline);
        $section->addTextBreak(1);
        
        $section->addText("Subject: BLACKLISTING THE ACCOUNT HOLDER", $boldUnderline);
        $section->addTextBreak(1);
        
        $customerName = $case->target->name_english ?? $case->target->name_nepali;
        $body1 = "We refer to the captioned matter. In this regard (payee Mr/Mrs/Ms) " . ($data['payee_name'] ?? '................') . " has requested for blacklisting (Accountholder Mr/Mrs/Ms) " . $customerName . " vide letter dated " . ($data['payee_application_date'] ?? '................') . ".";
        $section->addText($body1, $fontStyle);
        $section->addTextBreak(1);
        
        // Table 1: Cheque Details
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000'];
        $phpWord->addTableStyle('CoverTable1', $tableStyle);
        $table1 = $section->addTable('CoverTable1');
        $chequeRows = [
            ['Cheque No', $data['cheque_number'] ?? ''],
            ['Cheque Amount', $data['amount'] ?? ''],
            ['Cheque Issue Date', $data['cheque_issue_date'] ?? ''],
            ['Applicant/Payee Name', $data['payee_name'] ?? ''],
            ['Cheque return Date', $data['cheque_return_date'] ?? ''],
            ['Cheque return reason', $data['dishonour_reason'] ?? '']
        ];
        foreach ($chequeRows as $row) {
            $table1->addRow();
            $table1->addCell(4000)->addText($row[0], $fontStyle);
            $table1->addCell(5000)->addText($row[1], $fontStyle);
        }
        $section->addTextBreak(1);
        
        $section->addText("Legal Provision: As per the Article 9 of the NRB Unified Directives No 12/082 and Cheque Dishonour Certificate Procedure 2082", $fontStyle);
        $section->addText('"No one shall issue a cheque when there is no balance or insufficient balance in account. If the payee wishes to dishonour the cheque returned due to no balance or insufficient balance in the account, the bank or financial institution or cooperative bank that issue the cheque shall certify the cheque dishonour."', $fontStyle);
        $section->addTextBreak(1);
        $section->addText("In case where a cheque is presented by the Payee to a bank or financial institution or cooperative bank is returned for other reason other than those that the holder can verify (Wrong Signature, account closure, account freeze, stop payment etc) it must be verified that there is no balance in the relevant account or that the balance is not sufficient for payment, even in case where a cheque is returned for other reason , if there is no balance in the account or that the balance is not sufficient for payment the cheque must be certified dishonoured in accordance with this procedure.", $fontStyle);
        $section->addTextBreak(1);
        
        $section->addText("Based on NRB provisions we have complied with the following procedure:", $fontStyle);
        
        // Table 2: Compliance
        $phpWord->addTableStyle('CoverTable2', $tableStyle);
        $table2 = $section->addTable('CoverTable2');
        $complianceHeaders = ['SN', 'Particulars', 'Date', 'Compliance Status'];
        $table2->addRow();
        foreach ($complianceHeaders as $header) {
            $table2->addCell()->addText($header, $boldStyle);
        }
        $complianceRows = [
            ['1', "Payee's written application for informing cheque dishonor obtained by branch on", $data['payee_application_date'] ?? '', $data['comp_1'] ?? ''],
            ['2', "Formal Written notice (45 day's) sent to account holder by branch", $data['notice_date'] ?? '', $data['comp_2'] ?? ''],
            ['3', "Details of postal receipt or acknowledgement receipt should also be mentioned", $data['postal_receipt_date'] ?? '', $data['comp_3'] ?? ''],
            ['4', "Payee Written application for certification of cheque dishonour and / or blacklisting obtained by branch", $data['application_date'] ?? '', $data['comp_4'] ?? ''],
            ['5', "Cheque dishonour certificate issue date", $data['dishonour_certificate_date'] ?? '', $data['comp_5'] ?? ''],
            ['6', "Cheque stop Date", $data['cheque_stop_date'] ?? '', $data['comp_6'] ?? '']
        ];
        foreach ($complianceRows as $row) {
            $table2->addRow();
            $table2->addCell(500)->addText($row[0], $fontStyle);
            $table2->addCell(4500)->addText($row[1], $fontStyle);
            $table2->addCell(2000)->addText($row[2], $fontStyle);
            $table2->addCell(2000)->addText($row[3], $fontStyle);
        }
        $section->addTextBreak(1);
        
        $section->addText("In the above backdrop, as the accountholder has failed to deposit required amount of cheque in his account after the completion of 45 days and payee has remained in his stand to blacklist the account holder , we have complied with all the provisions as per NRB Directives No 12/082 and Cheque Dishonour Procedure 2082 and recommend to blacklist account holder as per the details mentioned below on an account of insufficient fund / " . ($data['dishonour_reason_extra'] ?? '........'), $fontStyle);
        $section->addTextBreak(1);
        
        $section->addText("Details of Account holders are as under:", $fontStyle);
        
        // Table 3: Individual Details
        $phpWord->addTableStyle('CoverTable3', $tableStyle);
        $table3 = $section->addTable('CoverTable3');
        $table3->addRow();
        $table3->addCell(500)->addText('SN', $boldStyle);
        $table3->addCell(4000)->addText('Account Holders Details', $boldStyle);
        $table3->addCell(4000)->addText('Remarks', $boldStyle);
        $indRows = [
            ['1', "Accountholder's Name", $customerName],
            ['2', "Account Number", $case->account_number],
            ['3', "Citizenship Number", $data['citizenship_number'] ?? ''],
            ['4', "Citizenship Issue District and Date", $data['citizenship_issue_details'] ?? ''],
            ['5', "Father's Name", $data['fathers_name'] ?? ''],
            ['6', "Grand Father's Name", $data['grandfathers_name'] ?? '']
        ];
        foreach ($indRows as $row) {
            $table3->addRow();
            $table3->addCell()->addText($row[0], $fontStyle);
            $table3->addCell()->addText($row[1], $fontStyle);
            $table3->addCell()->addText($row[2], $fontStyle);
        }
        $section->addTextBreak(1);
        
        $section->addText("Details of account holder in case of company are as under", $fontStyle);
        
        // Table 4: Company Details
        $phpWord->addTableStyle('CoverTable4', $tableStyle);
        $table4 = $section->addTable('CoverTable4');
        $table4->addRow();
        $table4->addCell(500)->addText('S.N', $boldStyle);
        $table4->addCell(4000)->addText('Account holder\'s Details', $boldStyle);
        $table4->addCell(4000)->addText('Remarks', $boldStyle);
        $compRows = [
            ['1', "Citizenship Number of Proprietor/Partners/Authorized Signatures", $data['company_citizenship_number'] ?? ''],
            ['2', "Citizenship Issue District and Date", $data['company_citizenship_details'] ?? ''],
            ['3', "Father name", $data['company_fathers_name'] ?? ''],
            ['4', "Grand Father Name", $data['company_grandfathers_name'] ?? ''],
            ['5', "Pan Number and issue date", $data['pan_details'] ?? ''],
            ['6', "Registration number and registration Date", $data['registration_details'] ?? '']
        ];
        foreach ($compRows as $row) {
            $table4->addRow();
            $table4->addCell()->addText($row[0], $fontStyle);
            $table4->addCell()->addText($row[1], $fontStyle);
            $table4->addCell()->addText($row[2], $fontStyle);
        }
        $section->addTextBreak(1);
        
        $section->addText("In this regards, we also confirm that we have recovered the necessary charges as per following", $fontStyle);
        
        // Table 5: Charges
        $phpWord->addTableStyle('CoverTable5', $tableStyle);
        $table5 = $section->addTable('CoverTable5');
        $table5->addRow();
        $table5->addCell(500)->addText('SN', $boldStyle);
        $table5->addCell(4000)->addText('Transaction ID/Date', $boldStyle);
        $table5->addCell(2000)->addText('Amount', $boldStyle);
        $table5->addCell(2000)->addText('Remarks', $boldStyle);
        
        $table5->addRow();
        $table5->addCell()->addText('1', $fontStyle);
        $table5->addCell()->addText($data['transaction_id_date'] ?? '', $fontStyle);
        $table5->addCell()->addText($data['charge_amount'] ?? '', $fontStyle);
        $table5->addCell()->addText($data['charge_remarks'] ?? '', $fontStyle);
        
        $section->addTextBreak(1);
        $section->addText("Necessary Document are enclosed herewith for your scrutiny.", $fontStyle);
        $section->addText("Remarks if any: " . ($data['final_remarks'] ?? '................'), $fontStyle);
        $section->addTextBreak(1);
        $section->addText("Submitted for your approval and necessary actions.", $fontStyle);
        $section->addTextBreak(3);
        
        $section->addText(".....................", $boldStyle);
        $section->addText("Branch Manager", $boldStyle);
        $section->addText(($data['branch_name'] ?? '................') . " Branch", $boldStyle);
        $section->addText("Date: " . ($data['eng_date'] ?? '................'), $boldStyle);
        
        $fileName = 'Cover_Note_' . str_replace('-', '_', $case->case_number) . '.docx';
        $filePath = storage_path('app/private/' . $fileName);
        
        if (!file_exists(storage_path('app/private/'))) {
            mkdir(storage_path('app/private/'), 0755, true);
        }
        
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($filePath);
        
        return $filePath;
    }
}
