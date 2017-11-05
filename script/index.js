/*
 * Tyler Filla
 * CS 4610
 * Project 2
 */

/**
 * All keywords currently entered in the search input box.
 * @type {Array}
 */
var searchKeywords = [];

/**
 * Render the current keywords as chips in the search area.
 */
function renderChips() {
    // Find stuff
    var searchChips = document.getElementById("search-chips");
    var searchInput = document.getElementById("search-input");

    // Delete all existing search chips
    while (searchChips.firstChild) {
        searchChips.removeChild(searchChips.firstChild);
    }

    // Create new chips for search keywords
    for (var i = 0; i < searchKeywords.length; ++i) {
        // Root element
        var chip = document.createElement("span");
        chip.classList.add("chip");
        chip.innerText = searchKeywords[i];

        // Delete button
        var chipDel = document.createElement("button");
        chipDel.classList.add("chip-del");
        chipDel.innerText = "x";

        // Add elements to search chip area
        searchChips.appendChild(chip);
        chip.appendChild(chipDel);

        // Add space between chips
        searchChips.appendChild(document.createTextNode(" "));

        // Listen for delete button clicks
        const idx = i;
        chipDel.addEventListener("click", function () {
            // Remove the associated keyword
            searchKeywords.splice(idx, 1);

            // Re-render the chips
            renderChips();
        }, false);
    }

    // Show placeholder on search input box if no keywords entered
    if (searchKeywords.length > 0) {
        searchInput.placeholder = "";
    } else {
        searchInput.placeholder = "Keyword search";
    }
}

/**
 * Initialize the keyword search system.
 */
function initializeSearch() {
    // Find stuff
    var searchInput = document.getElementById("search-input");
    var searchInputArea = document.getElementById("search-input-area");

    // Listen for search input key down
    searchInput.addEventListener("keydown", function (event) {
        if (event.key === ",") {
            // Move all typed text into a new keyword
            searchKeywords.push(this.value);
            this.value = "";

            // Render search chips
            renderChips();

            // Do not accept the comma as part of a keyword
            event.preventDefault();

            return;
        }

        // If input box is empty and backspace was pressed
        if (event.keyCode === 8 && this.value === "") {
            // Remove last keyword
            searchKeywords.pop();

            // Render search chips
            renderChips();

            return;
        }

        // Do not accept a space as the first character of a keyword
        if (event.key === " " && this.value === "") {
            event.preventDefault();
        }
    }, true);

    // Listen for search input blur
    searchInput.addEventListener("blur", function () {
        // If search input box is not empty
        if (this.value !== "") {
            // Move all typed text into a new keyword
            searchKeywords.push(this.value);
            this.value = "";

            // Render search chips
            renderChips();
        }
    }, false);

    // Redirect all focus attempts from search input area to search input
    searchInputArea.addEventListener("click", function () {
        searchInput.focus();
    }, false);

    // Render search chips
    renderChips();
}

/**
 * The list of all received problems.
 */
var problemList = [];

/**
 * Render the problem list into the result table.
 */
function renderTable() {
    // Find stuff
    var resultTable = document.getElementById("result-table");
    var resultTableTbody = resultTable.getElementsByTagName("tbody")[0];

    // Delete all existing table rows
    while (resultTableTbody.firstChild) {
        resultTableTbody.removeChild(resultTableTbody.firstChild);
    }

    // Create new table rows for problems
    for (var i = 0; i < problemList.length; ++i) {
        var problem = problemList[i];

        const problemPid = problem["pid"];
        const problemContent = problem["content"];

        // Row root element
        var row = document.createElement("tr");

        // Row order column
        var rowOrder = document.createElement("td");
        rowOrder.style.whiteSpace = "nowrap";
        rowOrder.appendChild(document.createTextNode(i + 1));
        row.appendChild(rowOrder);

        // Row ID column
        var rowId = document.createElement("td");
        rowId.style.whiteSpace = "nowrap";
        rowId.appendChild(document.createTextNode(problemPid));
        row.appendChild(rowId);

        // Row preview column
        var rowPreview = document.createElement("td");
        row.appendChild(rowPreview);

        // Problem content preview
        // Decode the Base64-encoded problem content and add it directly as HTML
        // We are trusting the server not to send anything malicious at this point
        var problemContentPreview = document.createElement("div");
        problemContentPreview.classList.add("content-preview");
        problemContentPreview.innerHTML = atob(problemContent);
        rowPreview.appendChild(problemContentPreview);

        // Row action column
        var rowAction = document.createElement("td");
        rowAction.style.whiteSpace = "nowrap";
        row.appendChild(rowAction);

        // Move up action button
        var actionUp = document.createElement("button");
        actionUp.classList.add("btn", "btn-default");
        rowAction.appendChild(actionUp);
        rowAction.appendChild(document.createTextNode(" "));

        // Listen for move up action button clicks
        actionUp.addEventListener("click", function () {
            alert("clicked MOVE UP action button on problem " + problemPid);
        }, false);

        // Icon for move up action button
        var actionUpIcon = document.createElement("span");
        actionUpIcon.classList.add("glyphicon", "glyphicon-chevron-up");
        actionUp.appendChild(actionUpIcon);

        // Move down action button
        var actionDown = document.createElement("button");
        actionDown.classList.add("btn", "btn-default");
        rowAction.appendChild(actionDown);
        rowAction.appendChild(document.createTextNode(" "));

        // Listen for move down action button clicks
        actionDown.addEventListener("click", function () {
            alert("clicked MOVE DOWN action button on problem " + problemPid);
        }, false);

        // Icon for move down action button
        var actionDownIcon = document.createElement("span");
        actionDownIcon.classList.add("glyphicon", "glyphicon-chevron-down");
        actionDown.appendChild(actionDownIcon);

        // Edit action button
        var actionEdit = document.createElement("button");
        actionEdit.classList.add("btn", "btn-default");
        rowAction.appendChild(actionEdit);
        rowAction.appendChild(document.createTextNode(" "));

        // Listen for edit action button clicks
        actionEdit.addEventListener("click", function () {
            alert("clicked EDIT action button on problem " + problemPid);
        }, false);

        // Icon for edit action button
        var actionEditIcon = document.createElement("span");
        actionEditIcon.classList.add("glyphicon", "glyphicon-pencil");
        actionEdit.appendChild(actionEditIcon);

        // Trash action button
        var actionTrash = document.createElement("button");
        actionTrash.classList.add("btn", "btn-default");
        rowAction.appendChild(actionTrash);

        // Listen for trash action button clicks
        actionTrash.addEventListener("click", function () {
            alert("clicked TRASH action button on problem " + problemPid);
        }, false);

        // Icon for trash action button
        var actionTrashIcon = document.createElement("span");
        actionTrashIcon.classList.add("glyphicon", "glyphicon-trash");
        actionTrash.appendChild(actionTrashIcon);

        // Add row to table
        resultTableTbody.appendChild(row);
    }
}

/**
 * Communicate with the list API endpoint.
 *
 * @param {Number} pageNum The desired page
 * @param {Number} pageSize The size of the page
 * @param {Function} callback A function to receive the result
 */
function apiList(pageNum, pageSize, callback) {
    var request = new XMLHttpRequest();
    request.open("GET", "/api/list.php?page_num=" + pageNum + "&page_size=" + pageSize, true);
    request.onreadystatechange = function () {
        if (request.readyState === XMLHttpRequest.DONE && request.status === 200) {
            var responseObject = JSON.parse(request.responseText);
            if (!responseObject["success"]) {
                callback("FAIL: " + responseObject["error"]);
            } else {
                callback(null, responseObject["result"]);
            }
        }
    };
    request.send();
}

/**
 * Communicate with the search API endpoint.
 *
 * @param {Array} keywords The keywords for which to search
 * @param {Function} callback A function to receive the result
 */
function apiSearch(keywords, callback) {
    var request = new XMLHttpRequest();
    request.open("GET", "/api/search.php?keywords=" + encodeURI(keywords.join(",")), true);
    request.onreadystatechange = function () {
        if (request.readyState === XMLHttpRequest.DONE && request.status === 200) {
            var responseObject = JSON.parse(request.responseText);
            if (!responseObject["success"]) {
                callback("FAIL: " + responseObject["error"]);
            } else {
                callback(null, responseObject["result"]);
            }
        }
    };
    request.send();
}

// Listen for window load
window.addEventListener("load", function () {
    // Initialize keyword search system
    initializeSearch();

    /*
    apiList(function (err, res) {
        if (err) {
            console.error("Unable to list problems");
            return;
        }

        problemList = res["problems"];
        renderTable();

        // Rerun MathJax typesetting on whole page
        MathJax.Hub.Queue(["Typeset", MathJax.Hub]);
    });
    */

    apiSearch(["triangle"], function (err, res) {
        if (err) {
            console.error("Unable to search problems");
            return;
        }

        problemList = res["problems"];
        renderTable();

        // Rerun MathJax typesetting on whole page
        MathJax.Hub.Queue(["Typeset", MathJax.Hub]);
    });
}, false);
