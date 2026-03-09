<?php
$file = 'hiring.php';
$content = file_get_contents($file);

// Find the modals section precisely
$start_str = '<!-- 1. Interview Modal -->';
$end_str = '<?php endwhile; else: ?>';

$start_pos = strpos($content, $start_str);
$end_pos = strpos($content, $end_str);

if ($start_pos !== false && $end_pos !== false) {
    // Extract modals - we also need to get spaces before the start_str, but let's just substring exactly.
    // Let's actually find the start of the line for start_str to keep it clean.
    $start_trimmed = strrpos(substr($content, 0, $start_pos), "\n") + 1;
    
    // Extract the content
    $modals_content = substr($content, $start_trimmed, $end_pos - $start_trimmed);
    
    // Remove the modals from existing location
    $content = substr_replace($content, '', $start_trimmed, $end_pos - $start_trimmed);
    
    // Create new wrap for modals
    $new_modals_block = "\n<!-- Extracted Modals -->\n<?php\nif(\$result && \$result->num_rows > 0):\n    \$result->data_seek(0);\n    while(\$row = \$result->fetch_assoc()):\n?>\n" 
                        . $modals_content 
                        . "<?php endwhile; endif; ?>\n";
    
    // Insert just before "<!-- Add Candidate Modal -->"
    $insert_target = '<!-- Add Candidate Modal -->';
    $insert_pos = strpos($content, $insert_target);
    
    if ($insert_pos !== false) {
        $content = substr_replace($content, $new_modals_block . "\n" . $insert_target, $insert_pos, strlen($insert_target));
        file_put_contents($file, $content);
        echo 'Success: Modals extracted and moved successfully.';
        unlink(__FILE__); // self-delete
    } else {
        echo 'Error: Failed to find insert target.';
    }
} else {
    echo 'Error: Failed to find modals section.';
}
?>
