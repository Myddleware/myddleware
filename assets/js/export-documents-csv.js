console.log("export-documents-csv.js loaded");

$("#exportfluxcsv").on("click", function () {
    $.ajax({
      type: "POST",
      url: flux_export_docs_csv,
      data: {
        csvdocumentids: csvdocumentids,
      },
      xhrFields: {
        responseType: 'blob' // Set the response type to blob
      },
      success: function (blob) {
        // Create a temporary URL for the blob
        const url = window.URL.createObjectURL(blob);
        
        // Create a temporary link element
        const a = document.createElement('a');
        a.href = url;
        a.download = `documents_export_${new Date().toISOString().slice(0,19).replace(/[:]/g, '')}.csv`;
        
        // Append link to body, click it, and remove it
        document.body.appendChild(a);
        a.click();
        
        // Clean up
        window.URL.revokeObjectURL(url);
        a.remove();
      },
      error: function(xhr, status, error) {
        console.error('Export failed:', error);
        alert('Failed to export CSV file. Please try again.');
      }
    });
  });
  
  // ---- EXPORT DOCUMENTS TO CSV  --------------------------------------------------------------------------