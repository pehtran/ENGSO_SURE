<?php

require 'vendor/autoload.php';

$phpWord = new \PhpOffice\PhpWord\PhpWord();
$section = $phpWord->addSection();
$section->addText(
    '"Learn from yesterday, live for today, hope for tomorrow. '
    . 'The important thing is not to stop questioning." '
    . '(Albert Einstein)'
);

$file = 'testni_mailto.docx';

// Save the document to a file
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save($file);



// Email configuration
$to = 'gasper@me.com';
$subject = 'Word Document Attachment';
$message = 'Please find the attached Word document.';

// Create the mailto link
$mailtoLink = "mailto:$to?subject=" . rawurlencode($subject) . "&body=" . rawurlencode($message);

// Open the default email client with attachment
$mailtoLink .= "&attachment=" . rawurlencode($file);
echo "<script>window.location.href = '$mailtoLink';</script>";

// Remove the generated Word document after opening the email client
//unlink($file);



?>



