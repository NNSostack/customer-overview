<?php

	include_once("common.php");

	$configPath = getDataPath() . "/config.json";
	$config = file_get_contents($configPath);
	$backUpPath = getDataPath() . "/backup"; 

	if(!is_dir($backUpPath)){
		mkdir($backUpPath);
	}
	
	$showMessage = null;
	$redirectUrl = null;

	if(!is_user_logged_in() && !isset($_GET["demo"])){
		$redirectUrl = '/wp-admin';
	}

	if(isset($_GET["token"])){
		$newCustomer = createCustomer($_GET["token"]);
		$customer = json_decode($newCustomer);
		
		if(property_exists($customer, "ErrorMessage")){
			$showMessage = $customer->ErrorMessage;
		}
		else{
			$showMessage = "'" . $customer->CompanyName . "' er nu tilføjet til dit dashboard";
		}
	}

	if(isset($_GET["addColumn"])){
		$json = json_decode($config);
		array_push($json->columns, json_decode('{ "Text": "' . $_GET["addColumn"] . '"}', true));
		
		$date = date('d-m-Y-His');
		copy(getDataPath() . "/config.json", $backUpPath . "/config-{$date}.json");
		file_put_contents($configPath, json_encode($json));
		$redirectUrl = strtok($_SERVER["REQUEST_URI"], '?');
	}



	if(isset($_GET["removeColumn"])){
		$columnName = $_GET["removeColumn"];
		$json = json_decode($config);

		$newColumns = [];
		$showMessage = "Kolonnen med navnet '{$columnName}' kunne ikke findes. Check stavning og store og små bogstaver";

		foreach($json->columns as $column){
			if($column->Text != $columnName){
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

	#headerRow1{
		display: none;
	}

	<?php } ?>

</style>

<script>
<?php
	if($redirectUrl != null && $showMessage == null){
		echo "location.href = '{$redirectUrl}';";
	}	
	else {
		echo "vouchersWithNoFilesAttached = null;\r\n";
		if($status == null) echo "statusObj = {};\r\n";
		else echo "statusObj = {$status};\r\n";
		echo "configObj = {$config};";
	}

	if(!Helper::IsElementor() && $redirectUrl == null){ 
?>	

	jQuery(document).ready(function(){
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
			vouchersWithNoFilesAttached = jQuery.parseJSON(response);
			updateData();
		});
	
		

		<? if($showMessage != null){?>
		setTimeout(function() {
			alert("<? echo $showMessage ?>");
			}, 200);
		<? } ?>
	});

	function updateData(accountId = '', timeout = 1000){
		jQuery('.spinner').fadeIn();

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

		if($noOfExtraCols == 0){
			jQuery("#removeColumn").hide();
		}

		if($noOfExtraCols >= 2){
			jQuery("#addColumn").hide();
		}

		for(var i = 0; i < vouchersWithNoFilesAttached.VouchersWithNoFilesAttached.length; i++){
			var company = vouchersWithNoFilesAttached.VouchersWithNoFilesAttached[i];

			hash = company.Hash;
			companyStatus = {};

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
				jQuery(".vouchers").hide();
				
				if(configObj.columns) {
					for(var iC = 0; iC < configObj.columns.length; iC++) {
						text = configObj.columns[iC].Text;

						columnStatus = {};
						columnStatushandled = false;
					
						if(companyStatus.columnStatus){
							columnStatushandled = companyStatus.columnStatus[text];
						}

						columnStatus = columnStatushandled != true ? ".templateUncomplete" : ".templateComplete";
						columnStatusElem = jQuery(columnStatus).clone();
						columnStatusElem.removeClass(columnStatus.replace(".", ""));
						columnStatusHtml = columnStatusElem.html();
						columnStatusHtml = columnStatusHtml.replace('[columnName]', text);

						var clone = jQuery('.newColumnCompany').clone();
						clone.removeClass('newColumnCompany');
						var html = clone.html();
						html = html.replace("[columnStatusIcon]", columnStatusHtml);
						item.find('.lastColumnCompany').parent().append(html);
					}
				}
				
				var itemTarget = null;
				company.Journals.reverse();
				for(var j = 0; j < company.Journals.length; j++){
					itemTarget = updateAndInsertTemplate('.templateJournal', ["journalName", company.Journals[j].JournalName], function(item2) {
						if(j != company.Journals.length - 1 || numberOfVouchersWithNoFileAttached == 0){
							//	Remove request button for all items except the first
							item2.find('.requestFiles').remove();
						}

						var itemTarget2 = null;
						for(var v = 0; v < company.Journals[j].Vouchers.length; v++){
							voucher = company.Journals[j].Vouchers[v];
							itemTarget2 = updateAndInsertTemplate('.templateVoucherText', ["voucherText", voucher.Text, "journalNo", 
								company.Journals[j].JournalNumber, "voucherNo", voucher.VoucherNumber, "accountingYear", voucher.AccountingYear, "hash", hash, 
								"fileStatus", hasFile(companyStatus, voucher.JournalNumber, voucher.AccountingYear, voucher.VoucherNumber) ? "hasFile" : "hasNoFile" ], 
							function(item2) {
							}, itemTarget2 ?? jQuery(item2).find('.voucherContent'));
						}
							
					}, itemTarget ?? jQuery(item).find('.journal'));
					
				}

			});
		}

		
		jQuery(".spinner").hide();	
		zindex = jQuery("#headerRow").css('z-index');
		jQuery("#mydiv").css('z-index', -200000);
		jQuery("#headerRow").fadeIn(function(){
			jQuery(window).trigger('resize');
			jQuery("#headerRow").css('z-index', zindex);
		});

		jQuery('.toggleVouchers').click(function(){
			parent = jQuery(this).parents(".companyParent");
			parent.find('.vouchers').slideToggle();
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

		attachCompleteUnCompleteEvents();

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
					alert("Der skete en fejl ved upload af bilaget. (" + json.message + ")");
			  }
			  else{
					hasNoFile = jQuery($this).parents(".hasNoFile");
					hasNoFile.removeClass('hasNoFile');
					hasNoFile.addClass('hasFile');
					alert("Bilag uploadet");
			  }
			});
		});
	}

	

	function attachCompleteUnCompleteEvents(){
		jQuery('.complete').off('click');
		jQuery('.uncomplete').off('click');

		jQuery('.complete').click(function(){
			jQuery(this).parent().html(jQuery('.templateUncomplete').html());
			attachCompleteUnCompleteEvents();
		});

		jQuery('.uncomplete').click(function(){
			jQuery(this).parent().html(jQuery('.templateComplete').html());
			attachCompleteUnCompleteEvents();
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