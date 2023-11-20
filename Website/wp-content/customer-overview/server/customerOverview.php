<?php

	include_once("common.php");
	
	$showMessage = null;
	$redirectUrl = null;

	$config = loadConfig();

	if(!is_user_logged_in()){
		$redirectUrl = '/wp-admin';
	}

	if(isAdmin()){
		$configPath = getDataPath() . "/config.json";
		$backUpPath = getDataPath() . "/backup"; 
		if(isset($_GET["addColumn"])){
			$json = $config;
			$newCol = json_decode('{ "Id": "' . sanitizeColumnName($_GET["addColumn"]) . '", "Text": "' . $_GET["addColumn"] . '"}', true);
			if($newCol != null){
				array_push($json->columns, $newCol);
				$date = date('d-m-Y-His');
				copy(getDataPath() . "/config.json", $backUpPath . "/config-{$date}.json");
				file_put_contents($configPath, json_encode($json));
				$redirectUrl = strtok($_SERVER["REQUEST_URI"], '?');
			}
			
		}

		if(isset($_GET["removeColumn"])){
			$columnName = $_GET["removeColumn"];
			$json = $config;
			$newColumns = [];
			$showMessage = "Kolonnen med navnet '{$columnName}' kunne ikke findes. Check stavning og store og små bogstaver";

			$sanitizedColName = sanitizeColumnName($columnName);
			foreach($json->columns as $column){
				if($column->Id != $sanitizedColName){
					array_push($newColumns, $column);
				}
				else{
					$showMessage = null;
				}
			}

			if($showMessage == ''){
				$json->columns = $newColumns;

				$date = date('d-m-Y-His');
				copy(getDataPath() . "/config.json", $backUpPath . "/config-{$date}.json");
				file_put_contents($configPath, json_encode($json));

				$redirectUrl = strtok($_SERVER["REQUEST_URI"], '?');
			}
		}
	}

	startApplication();
	$status = null;
	if($redirectUrl == null){
		if(file_exists(getDataPath() . "/status.json")){
			$status = file_get_contents(getDataPath() . "/status.json");
		}
	}

?>

<style>
	.hasNoFile {
		color: #D32D41;
	}

	.hasFile {
		color: #054B28;
	}


	<?php if(!Helper::IsElementor()){ ?>

	.hidden {
		display:none;
	}

	/* Tabs */
	.e-n-tabs-heading{
		display: none !important;
	}

	<?php } ?>

</style>

<script>
<?php
	$token = '';
	if(isset($_GET["token"]) && isAdmin()){ 
		$token = $_GET["token"];
	}

	if($redirectUrl != null && $showMessage == null){
		echo "location.href = '{$redirectUrl}';";
	}	
	else {
		echo "vouchersWithNoFilesAttached = null;\r\n";
		echo "statusObj = null;\r\n";
		echo "configObj = null;\r\n";
	}

	if(!Helper::IsElementor() && $redirectUrl == null){ 
?>	

	var shownTooltip = null;
	var toolTipParent = null;
	var currentMousePos = { x: -1, y: -1 };
	
	jQuery(document).ready(function(){

		jQuery(document).mousemove(function(event) {
			currentMousePos.x = event.pageX;
			currentMousePos.y = event.pageY;
		});

		loadStatus(function(data){
			statusObj = data;
			loadConfiguration(function(data){
				configObj = data;

				token = '<? echo $token ?>';
		
				if(token != ''){
					<? if(isAdmin()) { ?>
						updateStatusText("Opretter kunde");

						var settings = {
							url: '/wp-content/customer-overview/server/handleData.php?token=' + token,
							type: "POST",
							processData: false,
							contentType: false,
						};

						jQuery.ajax(settings).done(function (response) {
							if(response != ''){
								updateErrorText(response);
							}
							else{
								updateInfoText("Kunden er nu oprettet");
							}

							getData(updateData);
						});
					<? } ?>
				}
				else{
					getData(updateData);
				}
			});
		})

		<? if($showMessage != null){?>
		setTimeout(function() {
			updateErrorText("<? echo $showMessage ?>");
			}, 200);
		<? } ?>
	});

	function updateData(data){
		vouchersWithNoFilesAttached = data;

		$noOfExtraCols = 0;
		if(configObj.columns) {
			for(var i = 0; i < configObj.columns.length; i++) {
				$noOfExtraCols++;
				var clone = jQuery('.newColumnHeader').clone();
				clone.removeClass('newColumnHeader');
				var html = clone.html();
				html = html.replace("[columnText]", configObj.columns[i].Text);
				jQuery('.lastColumn').parent().append(html);
			}
		}

		
		if($noOfExtraCols <= 3){
			jQuery("#addColumn").show();
		}

		if($noOfExtraCols >= 0){
			jQuery("#removeColumn").show();
		}
		

		if(vouchersWithNoFilesAttached.VouchersWithNoFilesAttached.length == 0){
			updateInfoText("Ingen kunder fundet.", true);
		}

		availableCustomers = statusObj.customers['<? echo get_current_user_id() ?>'];

		for(var i = 0; i < vouchersWithNoFilesAttached.VouchersWithNoFilesAttached.length; i++){
			var company = vouchersWithNoFilesAttached.VouchersWithNoFilesAttached[i];

			hash = company.Hash;
			companyStatus = {};


			if(availableCustomers !== undefined && availableCustomers.indexOf(hash) == -1){
				continue;
			}

			if(statusObj.companies){
				companyStatus = statusObj.companies[company.Hash] ?? {};
			}

			numberOfVouchersWithNoFileAttached = getNumberOfVouchersWithNoFileAttached(company, companyStatus);
			voucherStatus = numberOfVouchersWithNoFileAttached > 0 ? 
				".templateExclamation" + (companyStatus.filesRequested ? "Yellow" : "") : ".templateCheckMark";
			voucherStatusElem = jQuery(voucherStatus).clone();
			voucherStatusElem.removeClass(voucherStatus.replace(".", ""));
			
			voucherStatusHtml = voucherStatusElem.html();
			voucherStatusHtml = voucherStatusHtml.replace("[requestDate]", companyStatus.filesRequested);
			voucherStatusHtml = voucherStatusHtml.replace("[noOfMissingFiles]", numberOfVouchersWithNoFileAttached);		

			updateAndInsertTemplate('.templateCustomer', ["customerName", company.CompanyName, "statusIcon", voucherStatusHtml], function(item){
				jQuery(".moreInfo").hide();

				if(configObj.columns) {
					for(var iC = 0; iC < configObj.columns.length; iC++) {
						column = configObj.columns[iC];
					
						text = column.Id;

						columnStatus = {};
						columnStatushandled = false;
					
						colStatus = companyStatus.columnStatus;
						if(colStatus && colStatus[text]){
							colStatus = colStatus[text];
							
							if(column.SubTasks){
								if(colStatus.subTasks){
									columnStatushandled = true;
									for(var st = 0; st < column.SubTasks.length; st++){
										if(!colStatus.subTasks[column.SubTasks[st].Id]){
											columnStatushandled = false;
										}
									}
								}
							}
							else{
								columnStatushandled = colStatus.status;
							}
						}

						columnStatus = columnStatushandled != true ? "#templateUncomplete" : "#templateComplete";
						columnStatusHtml = getCheckTemplate(columnStatus, hash, text);

						var clone = jQuery('.newColumnCompany').clone();
						clone.removeClass('newColumnCompany');
						clone.addClass('toggleMoreInfo');

						var html = clone.html();
						html = html.replace("[columnStatusIcon]", columnStatusHtml);
						
						periodIconHtml = "";

						if(column.Periods){
							clone = jQuery('#templatePeriodIcon').clone();
							periodIconHtml = clone.html();
							periodIconHtml = periodIconHtml.replace("[columnName]", text).replace("[itemId]", hash); 
						}

						html = html.replace("[periodIcon]", periodIconHtml);

						html = html.replace("[columnSubtasks]", "");
						item.find('.lastColumnCompany').parent().append(html);
					}
				}
				
				var itemTarget = null;
				company.Journals.reverse();
				
				missingFiles = false;
				
				for(var j = 0; j < company.Journals.length; j++){
					itemTarget = updateAndInsertTemplate('.templateJournal', ["journalName", company.Journals[j].JournalName], function(item2) {
						if(j != company.Journals.length - 1 || numberOfVouchersWithNoFileAttached == 0){
							//	Remove request button for all items except the first
							item2.find('.requestFiles').remove();
						}

						var itemTarget2 = null;
						for(var v = 0; v < company.Journals[j].Vouchers.length; v++){
							voucher = company.Journals[j].Vouchers[v];

							fileStatus = hasFile(companyStatus, voucher.JournalNumber, voucher.AccountingYear, voucher.VoucherNumber) ? "hasFile" : "hasNoFile";
							itemTarget2 = updateAndInsertTemplate('.templateVoucherText', ["voucherText", voucher.Text, "journalNo", 
								company.Journals[j].JournalNumber, "voucherNo", voucher.VoucherNumber, "accountingYear", voucher.AccountingYear, "hash", hash, 
								"fileStatus", fileStatus ], 
							function(item2) {
								if(fileStatus !== "hasFile"){
									missingFiles = true;
								}
							}, itemTarget2 ?? jQuery(item2).find('.voucherContent'));
						}
							
					}, itemTarget ?? jQuery(item).find('.journal'));
				}

				if(missingFiles){
					item.find('.noVouchersAreMissing').hide();
				}

			});
		}
				
		jQuery(".tooltip").each(function(){
			tooltip = jQuery(this).clone();
			tooltip.addClass("theTooltip");
			tooltip.hide();
			tooltip.click(function(event){
				event.stopPropagation();
			})

			jQuery('div[data-elementor-type="wp-page"]').append(tooltip);
		});

		jQuery(".spinner").hide();	
		zindex = jQuery("#headerRow").css('z-index');
		jQuery("#mydiv").css('z-index', -200000);
		jQuery("#headerRow").fadeIn(function(){
			jQuery(window).trigger('resize');
			jQuery("#headerRow").css('z-index', zindex);
		});

		jQuery('.toggleMoreInfo').click(function(){
			parent = jQuery(this).parents(".companyParent");
			showMoreInfo(parent, false);
			return false;
		});

		jQuery('#addColumn').click(function(){
			var newColumnName = prompt("Indtast navnet på den nye kolonne");

			if(newColumnName && newColumnName != ''){
				location.href = "?addColumn=" + newColumnName;
			}
			return false;
		});

		jQuery('#removeColumn').click(function(){
			var columnToDelete = prompt("Indtast navnet på kolonnen, der skal slettes. Inklusiv store bogstaver");

			if(columnToDelete && columnToDelete != ''){
				location.href = "?removeColumn=" + columnToDelete;
			}
			return false;
		});

		attachClickEvents(function(clickedElement, itemId, colName){
			
			if(jQuery(clickedElement).hasClass('taskName')){
				handleTaskNameClick(clickedElement);
				return true;
			}

			if(jQuery(clickedElement).hasClass('periodIcon')){
				handlePeriodIconClick(clickedElement, colName, itemId);
				return true;
			}

			//	Dont move the tooltip if in the tooltip itself
			if(jQuery(clickedElement).parents('.tooltip_period').length > 0){
				resetPeriods();
				return true;
			}

			company = statusObj.companies[itemId];
			if(!company){
				company = {};
				statusObj.companies[itemId] = company;
			}

			colStatus = company.columnStatus;
			if(!colStatus){
				colStatus = {};
				company.columnStatus = colStatus; 
			}

			col = colStatus[colName];
			if(!col){
				col = {};
				colStatus[colName] = col;
			}

			isComplete = jQuery(clickedElement).hasClass('uncomplete');

			if(jQuery(clickedElement).parents('.tab-subTasks').length > 0){
				taskId = jQuery(clickedElement).attr("data-taskid");
				
				subTasks = col.subTasks;
				if(!subTasks){
					subTasks = {};
					col.subTasks = subTasks;
				}

				subTasks[taskId] = isComplete;

				uncomplete = jQuery(clickedElement).parents('.tab-subTasks').find('.uncomplete.checkTemplate');
				$companyParent = jQuery(clickedElement).parents('.companyParent').find('.checkTemplate[data-columnname="' + colName + '"]').first();
				if(uncomplete.length == 1 && isComplete){
					$companyParent.parent().html(getCheckTemplate('#templateComplete', itemId, colName));
				}
				else{
					$companyParent.parent().html(getCheckTemplate('#templateUncomplete', itemId, colName));
				}

				saveStatusObj();
				return true;
			}
			
			if(shownTooltip != null){
				closePeriodTooltip();
			}	
			
			column = jQuery.grep(configObj.columns, function(item, index){
					return item.Id == colName;
				})[0];

			//	Is there subtasks? Then show them
			if(column.SubTasks){
				parent = jQuery(clickedElement).parents(".companyParent");
				
				parent.find('.subTaskPlaceholder').empty();
				for(var iSubTask = 0; iSubTask < column.SubTasks.length; iSubTask++){
					var template = "templateSubTaskUnComplete";

					colStatus = statusObj.companies[itemId].columnStatus;
					if(colStatus && colStatus[colName]){
						subtasks = colStatus[colName].subTasks;

						if(subtasks){
							if(subtasks[column.SubTasks[iSubTask].Id]){
								template = "templateSubTaskComplete";
							}
						}
					}

					var clone = jQuery('.' + template).clone();
					clone.removeClass(template);
					var html = clone.html();
					html = html.replace("[taskName]", column.SubTasks[iSubTask].Text);
					html = html.replace("[columnName]", colName);
					html = html.replace("[itemId]", itemId);
					html = html.replace("[taskId]", column.SubTasks[iSubTask].Id);
					
					parent.find('.subTaskPlaceholder').append(html);
				}
					
				showMoreInfo(parent, true, colName);
				return false;
			}

			col.status = isComplete;
			saveStatusObj();

			return true;
		});

		jQuery(window).click(function(){
			if(shownTooltip != null){
				closePeriodTooltip();
			}
		});

		jQuery('input[type="file"]').on("change", function(){
			var parent = jQuery(this).parents(".parent");
			var $this = this;

			year = parent.attr("data-accountingyear");
			journalNo = parent.attr("data-journalno");
			voucherNo = parent.attr("data-voucherno");
			hash = parent.attr("data-hash");
			file = this.files[0];

			var form = new FormData();
			form.append("file", file);

			var settings = {
			  url: '/wp-content/customer-overview/server/handleData.php?JournalNo='+journalNo+'&AccountingYear='+year+'&VoucherNo='+voucherNo+'&hash='+hash,
			  type: "POST",
			  processData: false,
			  contentType: false,
			  data: form
			};

			jQuery.ajax(settings).done(function (response) {
			  if(response !== "OK"){
					json = jQuery.parseJSON(response);
					updateErrorText("Der skete en fejl ved upload af bilaget. (" + json.message + ")");
			  }
			  else{
					hasNoFile = jQuery($this).parents(".hasNoFile");
					hasNoFile.removeClass('hasNoFile');
					hasNoFile.addClass('hasFile');
					updateInfoText("Bilag uploadet");
			  }
			});
		});

		jQuery('.closePeriodTooltiip').click(function(){
			closePeriodTooltip();
		});
	}
	


	function closePeriodTooltip(){
		shownTooltip.hide();
		shownTooltip = null;
		toolTipParent = null;
	}

	function handlePeriodIconClick($this, colName, itemId){
	
		toolTipParentKey = itemId + "-" + colName;

		if(toolTipParentKey == toolTipParent){
			closePeriodTooltip();
			return;
		}

		jQuery('.theTooltip.tooltip_period').each(function(){
			//	Show period
			column = jQuery.grep(configObj.columns, function(item, index){
				return item.Id == colName;
			})[0];
			
			period = "2";
				
			if(statusObj.companies[itemId].columnStatus && statusObj.companies[itemId].columnStatus[colName]){
				period = statusObj.companies[itemId].columnStatus[colName].period ?? "2";
			}

			parent = jQuery(this);
			parent.find('.periodPlaceholder').empty();

			for(var iPeriod = 0; iPeriod < column.Periods.length; iPeriod++){
				var template = "templateSubTaskUnComplete";

				if(iPeriod + 1 == period){
					template = "templateSubTaskComplete";
				}

				var clone = jQuery('.' + template).clone();
				clone.removeClass(template);
				var html = clone.html();

				html = html.replace("[taskName]", column.Periods[iPeriod].Text + " " + colName.toLowerCase());
				html = html.replace("[columnName]", colName);
				html = html.replace("[itemId]", itemId);
				html = html.replace("[taskId]", iPeriod + 1);
					
				parent.find('.periodPlaceholder').append(html);
			}

			jQuery(this).css('top', currentMousePos.y + 15);
			jQuery(this).css('left', currentMousePos.x + 15);
			jQuery(this).show();
			shownTooltip = jQuery(this);
			toolTipParent = toolTipParentKey;
		});
	}

	//	Handle click on text for tasks
	function handleTaskNameClick(taskName){
		jQuery(taskName).parents('.parent').find(".checkTemplate a").trigger('click');
	}

	function showMoreInfo(parent, isSubTasks = false, colName = null){
			
		moreInfo = parent.find('.moreInfo');
		subTasks = parent.find('.tab-subTasks');
		vouchers = parent.find('.tab-vouchers');

		oldColName = moreInfo.attr("data-colName");
		
		moreInfoVisible = moreInfo.is(":visible");
		subTasksVisible = subTasks.is(":visible");
		vouchersVisible = vouchers.is(":visible");

		if(isSubTasks && !subTasksVisible){
			subTasks.show();
			vouchers.hide();
		}
		else if(!isSubTasks && !vouchersVisible){
			subTasks.hide();
			vouchers.show();
		}
			
		if(!moreInfoVisible || (isSubTasks && subTasksVisible && colName == oldColName) || (!isSubTasks && vouchersVisible) ){
			moreInfo.slideToggle();
		}

		moreInfo.attr("data-colName", colName);
	}

	function saveStatusObj(){
		var form = new FormData();
		form.append("saveData", "status.json");
		form.append("data", JSON.stringify(statusObj));

		var settings = {
			url: '/wp-content/customer-overview/server/saveData.php',
			type: "POST",
			processData: false,
			contentType: false,
			data: form
		};

		jQuery.ajax(settings).done(function (response) {
			if(response !== "OK"){
				json = jQuery.parseJSON(response);
				updateErrorText("Der skete en fejl ved gemning. (" + json.message + ")");
			}
		});
	}

	function resetPeriods(){
		jQuery('.tooltip_period .checkTemplate').each(function(){
			jQuery(this).parent().html(getCheckTemplate('#templateUncomplete', itemId, colName));
		});
	}

	function getNumberOfVouchersWithNoFileAttached(company, companyStatus){
		count = 0;	
		for(c = 0; c < company.Journals.length; c++){
			for(v = 0; v < company.Journals[c].Vouchers.length; v++){
				voucher = company.Journals[c].Vouchers[v];
				if(!hasFile(companyStatus, voucher["JournalNumber"], voucher["AccountingYear"], voucher["VoucherNumber"])) {
					count++;
				}
			}
		}
		return count;
	}

	function hasFile(statusObj, journalNumber, year, voucherNumber){
		if(companyStatus.filesUploaded == undefined){
			return false;
		}

		fileStatus = companyStatus.filesUploaded[journalNumber + "-" + year + "-" + voucherNumber];
		return fileStatus == true;
	}


	<? } ?>

</script>