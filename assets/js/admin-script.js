jQuery(document).ready(function ($) {
    $('#start-scan').on('click', function () {
        const progressBar = $('#progress');
        const status = $('#scan-status');
        const results = $('#scan-results');
        const allBrokenImages = []; // Accumulate all broken images
        const scanButton = $(this); // Reference to the scan button
        let found = 0;
        let offset = 0;

        // Disable the scan button while scanning
        scanButton.prop('disabled', true).text('Scanning...');

        status.text('Scanning...');
        results.empty();
        progressBar.css('width', '0%');

        function processBatch() {
            $.ajax({
                url: ebisAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ebis_scan_images',
                    nonce: ebisAjax.nonce,
                    offset: offset, // Send the current offset
                },
                success: function (response) {
                    // Update progress bar and status
                    progressBar.css('width', response.progress + '%');
                    status.text(response.progress + '% completed. Broken links found: ' + found);

                    // Append new broken images to results
                    if (response.broken_images.length > 0) {
                        allBrokenImages.push(...response.broken_images); // Add to global array
                        if (results.find('table').length === 0) {
                            results.html('<table><thead><tr><th>Post Title</th><th>Broken URL</th></tr></thead><tbody></tbody></table>');
                        }
                        response.broken_images.forEach(function (image) {
                            results.find('tbody').append(
                                `<tr>
                                    <td><a href="${ebisAjax.siteurl}/wp-admin/post.php?post=${image.post_id}&action=edit" target="_blank">${image.post_title}</a></td>
                                    <td>${image.image_url}</td>
                                </tr>`
                            );
                        });
                        found += response.broken_images.length;
                    }

                    // Check if completed or continue to next batch
                    if (!response.completed) {
                        offset = response.offset; // Update offset for next batch
                        processBatch(); // Recursive call for next batch
                    } else {
                        // Update the button to be a "Download CSV" button
                        scanButton.prop('disabled', false).text('Download CSV').off('click').on('click', function () {
                            // Generate and download CSV
                            let csvContent = "data:text/csv;charset=utf-8,";
                            csvContent += "Post Title, Broken URL\n"; // Header row

                            allBrokenImages.forEach(function (image) {
                                csvContent += `"${image.post_title}","${image.image_url}"\n`;
                            });

                            const encodedUri = encodeURI(csvContent);
                            const link = document.createElement('a');
                            const datetime = new Date().toISOString().replace(/[:.]/g, '-');
                            const filename = `${ebisAjax.sitename}-broken-image-scanner-${datetime}.csv`;

                            link.setAttribute('href', encodedUri);
                            link.setAttribute('download', filename);
                            document.body.appendChild(link); // Required for Firefox
                            link.click();
                            document.body.removeChild(link);
                        });
                        status.text('100% completed. Broken links found: ' + found);
                    }
                },
                error: function () {
                    status.text('An error occurred during the scan. Please try again.');
                    scanButton.prop('disabled', false).text('Start Scan'); // Revert button on error
                },
            });
        }

        // Start the first batch
        processBatch();
    });
});
