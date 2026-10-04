<?php
$pdfPath = 'test_report_30.pdf';

if (file_exists($pdfPath)) {
    $fileSize = filesize($pdfPath);
    $content = file_get_contents($pdfPath);
    
    // Check for regulation content in PDF
    $hasRegulation = strpos($content, 'Tidak ada indikasi pelanggaran') !== false;
    $hasLaw = strpos($content, 'law') !== false || strpos($content, 'Law') !== false;
    $hasArticle = strpos($content, 'article') !== false || strpos($content, 'Article') !== false;
    
    echo "=== PDF VALIDATION ===\n";
    echo "File size: " . $fileSize . " bytes\n";
    echo "PDF exists: YES ✅\n";
    echo "Has regulation reference text: " . ($hasRegulation ? "YES ✅" : "NO ❌") . "\n";
    echo "Has law/article keywords: " . (($hasLaw || $hasArticle) ? "YES ✅" : "NO ❌") . "\n";
    
    // Show first 500 chars for debugging
    echo "\n=== PDF CONTENT PREVIEW (first 500 chars) ===\n";
    echo substr($content, 0, 500) . "\n";
    
    echo "\n✅ PDF CHECK COMPLETE\n";
} else {
    echo "❌ PDF file not found\n";
}
?>
