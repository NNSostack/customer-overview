<?php

	include_once("common.php");

	startApplication();
?>


<script>

var users = null;	
var statusObj = null;
var configObj = null;


<? if(!Helper::IsElementor()) { ?> 
jQuery(document).ready(function(){

	<? if(!IsAdmin()){ ?>
	updateStatusText("Du har ikke adgang");
	<? } else { ?>
	
	loadStatus(function(data){
		statusObj = data;
		loadConfiguration(function(data){ 
			configObj = data; 
	
			getData(function(data){
				var settings = {
					url: '/wp-content/customer-overview/server/getUsers.php',
					type: "GET"
				};

				jQuery.ajax(settings).done(function (response) {
					users = jQuery.parseJSON(response);

					for(var j = 0; j < users.length; j++){
						user = users[j];
			
						userStatus = user.active ? 
							".templateComplete" : ".templateUncomplete";
						userStatusHtml = getCheckTemplate(userStatus, user.id, "userActive");

						userAdmin = user.admin ? 
							".templateComplete" : ".templateUncomplete";
						userAdminHtml = getCheckTemplate(userAdmin, user.id, "userAdmin");

						customersText = "Adgang til alle kunder";
						customers = statusObj.customers[user.id];
						if(customers !== undefined){
							customersText = "Adgang til " + customers.length + " kunde" + (customers.length != 1 ? "r" : "");
						}

						updateAndInsertTemplate('.templateUser', ["fullName", user.fullName, "activeIcon", userStatusHtml, "adminIcon", userAdminHtml, "customers", customersText, "userId", user.id], function(item){
							item.find('.customers').hide();

							placeholderActive = item.find('.customerPlaceholderActive');
							placeholder = item.find('.customerPlaceholder');

							if(customers !== undefined){
								item.find('.userHasAccessToAll').hide();
							}
					
							for(var c = 0; c < data.VouchersWithNoFilesAttached.length; c++){
								company = data.VouchersWithNoFilesAttached[c];
					
								active = customers !== undefined && customers.indexOf(company.Hash) > -1;
								customerStatus = active ? 
									".templateComplete" : ".templateUncomplete";
								customerStatusHtml = getCheckTemplate(customerStatus, company.Hash, "customerAccess");
												
								updateAndInsertTemplate('.templateCustomer', ["customerName", company.CompanyName, "statusIcon", customerStatusHtml], null, active ? placeholderActive : placeholder);
							}
				
						});
					}

					attachClickEvents();

					jQuery('.toggleCustomers').click(function(){
						parent = jQuery(this).parents(".userParent");
						parent.find('.customers').slideToggle();
						return false;
					});

					jQuery(".spinner").hide();	
				});
			});	
		
		});
	})

	<? } ?>
});

<? } else { ?>



<? } ?>



</script>

	