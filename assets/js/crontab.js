// Creates a function that will be called when a button with the class crontab_class
// is clicked. The function will send a request to the server for the function with the following name crontab_show


// if an element with the class table-head-result is clicked, then call the function sortTable with the id of the element as an argument
$(document).on('click', '.table-head-result', function() {
    console.log('click on table-head-result');
    var id = $(this).attr('id');
    console.log(id);
    sortTable(id);
});

function sortTable(n) {
    var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    table = document.getElementById("myTable");
    switching = true;
    dir = "asc"; 

    // Convert n to a number
    var columnIndex = parseInt(n, 10); // Parse n as an integer
    console.log("Column Index: ", columnIndex);
  
    console.log(n)
  
    // Update the h5 content to show the current sorting direction
    var orderInfo = document.getElementById("crontab-order-info");
    orderInfo.innerText = "Sorting: " + (dir === "asc" ? "Ascending" : "Descending");
  
    while (switching) {
      switching = false;
      rows = table.rows;
  
      for (i = 1; i < (rows.length - 1); i++) {
        shouldSwitch = false;
        x = rows[i].getElementsByTagName("TD")[n];
        y = rows[i + 1].getElementsByTagName("TD")[n];
  
        // Check if we are dealing with dates
        if (columnIndex === 1 || columnIndex === 2 || columnIndex === 4) { // assuming date columns are 1 (Date created) and 2 (Date update) and 4 (Run at)
          var xDate = new Date(x.innerHTML);
          var yDate = new Date(y.innerHTML);
        } else {
          var xContent = isNaN(parseInt(x.innerHTML)) ? x.innerHTML.toLowerCase() : parseInt(x.innerHTML);
          var yContent = isNaN(parseInt(y.innerHTML)) ? y.innerHTML.toLowerCase() : parseInt(y.innerHTML);
        }
  
        if (columnIndex === 1 || columnIndex === 2 || columnIndex === 4) { // date columns
          if (dir == "asc") {
            if (xDate > yDate) {
              shouldSwitch = true;
              break;
            }
          } else if (dir == "desc") {
            if (xDate < yDate) {
              shouldSwitch = true;
              break;
            }
          }
        } else { // other columns
          if (dir == "asc") {
            if (xContent > yContent) {
              shouldSwitch = true;
              break;
            }
          } else if (dir == "desc") {
            if (xContent < yContent) {
              shouldSwitch = true;
              break;
            }
          }
        }
      }
  
      if (shouldSwitch) {
        rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
        switching = true;
        switchcount++;      
      } else {
        if (switchcount == 0 && dir == "asc") {
          dir = "desc";
          switching = true;
          // Update the h5 content when the direction changes
          orderInfo.innerText = "Sorting: Descending";
        }
      }
    }
  
    // Final update in case no switching was done and the direction is already descending
    if (!switching && dir === "desc") {
      orderInfo.innerText = "Sorting: Descending";
    }
  }
  