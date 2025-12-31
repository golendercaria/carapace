document.addEventListener('DOMContentLoaded', function() {
	// Cibler l'élément avec l'ID wp-admin-bar-vault_status
	var vaultStatus = document.getElementById('wp-admin-bar-vault_status');
	
	// Vérifier si l'élément existe avant d'ajouter l'événement
	if (vaultStatus) {
		vaultStatus.addEventListener('click', function () {
			
			if (this.classList.contains("lock") == true) {
				// Ajouter ou enlever la classe 'toggle'
				let toggle = this.classList.toggle('request_toggle_status');

				if (toggle) {
					let inputField = document.querySelector("#wp-admin-bar-vault_status input")
					if (inputField) {
						inputField.focus();
					}
				}
			} else { 

				let data = {
					action: 'carapace_lock_vault'
				};

				fetch(data_for_js.ajax_URL, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: new URLSearchParams(data).toString()
				})
				.then(response => response.json())  // Réponse JSON
				.then(responseData => {
					if (responseData.success) {
						location.reload();
					} else {
						alert("Une erreur est survenue");
					}
				})
				.catch(error => {
					console.error('Erreur AJAX :', error);
				});

			}

		});
	}



	document.addEventListener('keydown', function(e) {
		// Vérifier si la touche pressée est Enter (keyCode 13 ou 'Enter')
		if (e.key === 'Enter' || e.keyCode === 13) {
			// Vérifier si l'élément avec la classe toggle existe
			if (vaultStatus.classList.contains('request_toggle_status')) {
				

				let carapace_password = document.querySelector("#wp-admin-bar-vault_status input").value
				
				let data = {
					action: 'carapace_unlock_vault',
					password: carapace_password
				};

				fetch(data_for_js.ajax_URL, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: new URLSearchParams(data).toString()
				})
				.then(response => response.json())  // Réponse JSON
				.then(responseData => {
					if (responseData.success) {
						location.reload();
					} else {
						alert(responseData.data);
					}
				})
				.catch(error => {
					console.error('Erreur AJAX :', error);
				});
				
			}
		}
	});
});


