function updateStatusText(text) {
	jQuery('#statusText h2').html(text);
	jQuery('#statusBox').show()
	showSpinner();
}

function updateErrorText(text) {
	jQuery('#errorMessage h3').html(text);
	jQuery('#errorMessage').show()
	jQuery('#messageContainer').show();

	jQuery('#errorMessage').fadeOut(10000);
	jQuery('#messageContainer').fadeOut(10000);
}

function updateInfoText(text, permanent = false) {
	jQuery('#infoMessage h3').html(text);
	jQuery('#infoMessage').show();
	jQuery('#messageContainer').show();

	if (!permanent) {
		jQuery('#infoMessage').fadeOut(10000);
		jQuery('#messageContainer').fadeOut(10000);
	}
}

function getData(callback) {
	updateStatusText("Henter data");

	var form = new FormData();
	form.append("getData", "dashboard.json");

	var settings = {
		url: '/wp-content/customer-overview/server/handleData.php',
		type: "POST",
		processData: false,
		contentType: false,
		data: form
	};

	jQuery.ajax(settings).done(function (response) {
		callback(jQuery.parseJSON(response));
		hideSpinner();
	});
}

function loadConfiguration(callback) {
	loadData("config.json", callback);
}

function loadStatus(callback) {
	loadData("status.json", callback);
}

function loadData(dataType, callback) {
	updateStatusText("Henter data");

	var form = new FormData();
	form.append("getData", dataType);

	var settings = {
		url: '/wp-content/customer-overview/server/getData.php',
		type: "POST",
		processData: false,
		contentType: false,
		data: form,
		dataType: "json"
	};

	jQuery.ajax(settings).done(function (response) {
		callback(response);
		hideSpinner();
	});
}

$spinnerCount = 0;
function hideSpinner() {
	$spinnerCount--;

	if ($spinnerCount == 0) {
		jQuery('.spinner').hide();
    }
}

function showSpinner() {
	$spinnerCount++;
	jQuery('.spinner').show();
}

function getCheckTemplate(selector, id, columnName, taskId = null) {
	templateElem = jQuery(selector).clone();
	templateElem.removeClass(selector.replace(".", ""));

	if (selector.indexOf("#") > -1) {
		templateElem.removeAttr("id");
	}

	templateElemHtml = templateElem.html().replace("[itemId]", id).replace("[columnName]", columnName).replace("[taskId]", taskId);
	return templateElemHtml;
}

function attachClickEvents(callback) {
	jQuery('.complete').off('click');
	jQuery('.uncomplete').off('click');
	jQuery('.periodIcon').off('click');
	jQuery('.taskName').off('click');
	

	jQuery('.complete').click(function () {
		return handleClick(this, '#templateUncomplete', callback);
	});

	jQuery('.uncomplete').click(function () {
		return handleClick(this, '#templateComplete', callback);
	});

	jQuery('.periodIcon').click(function () {
		return handleClick(this, null, callback);
	});

	jQuery('.taskName').click(function () {
		return handleClick(this, null, callback);
	});
}

function handleClick($this, templateSelector, callback) {
	itemId = jQuery($this).attr("data-itemid");
	colName = jQuery($this).attr("data-columnname");
	taskId = jQuery($this).attr("data-taskid");
	userId = jQuery($this).parents('.userRoot').attr("data-userid");

	$parent = jQuery($this).parent();

	$continue = true;
	if (callback) {
		$continue = callback($this, itemId, colName);
	}
	
	if ($continue && templateSelector != null) {
		$parent.html(getCheckTemplate(templateSelector, itemId, colName, taskId));
	}
	attachClickEvents(callback);
	return false;
}