(function(){
	function qs(sel){ return document.querySelector(sel); }
	function on(el, ev, fn){ if(el){ el.addEventListener(ev, fn); } }
	function setProgress(completed, total, percent){
		var bar = qs('#icart-dl-progress');
		var fill = qs('#icart-dl-progress-fill');
		var txt = qs('#icart-dl-progress-text');
		if(!bar || !fill || !txt){ return; }
		bar.style.display = 'block';
		fill.style.width = Math.max(0, Math.min(100, percent)) + '%';
		txt.textContent = completed + '/' + total + ' completed (' + percent + '%)';
	}

	async function uploadAndStart(){
		var fileInput = qs('#icart-dl-ajax-file');
		var filenameInput = qs('#icart-dl-ajax-filename');
		var buildJson = qs('#icart-dl-ajax-build-json');
		if(!fileInput || !fileInput.files || fileInput.files.length === 0){
			alert('Please choose a CSV file first.');
			return;
		}
		var fd = new FormData();
		fd.append('action', 'icart_dl_upload_keywords');
		fd.append('nonce', ICartDLAdmin.nonce);
		fd.append('file', fileInput.files[0]);
		if(filenameInput && filenameInput.value){ fd.append('filename', filenameInput.value); }
		try{
			var res = await fetch(ICartDLAdmin.ajaxUrl, { method: 'POST', body: fd });
			var json = await res.json();
			if(!json || !json.success){ throw new Error(json && json.data && json.data.message ? json.data.message : 'Upload failed'); }
			var jobId = json.data.job_id;
			var total = json.data.total || 0;
			setProgress(0, total, 0);
			if(buildJson && buildJson.checked){
				processJob(jobId, total);
			} else {
				alert('Upload complete. Generation skipped.');
			}
		}catch(e){
			alert('Error: ' + (e && e.message ? e.message : e));
		}
	}

	async function processJob(jobId, total){
		var done = false;
		while(!done){
			var fd = new FormData();
			fd.append('action', 'icart_dl_process_keywords');
			fd.append('nonce', ICartDLAdmin.nonce);
			fd.append('job_id', jobId);
			fd.append('batch', '5');
			var res = await fetch(ICartDLAdmin.ajaxUrl, { method: 'POST', body: fd });
			var json = await res.json();
			if(!json || !json.success){
				alert('Processing failed');
				return;
			}
			setProgress(json.data.completed, json.data.total, json.data.percent);
			done = !!json.data.done;
			if(!done){
				await new Promise(function(r){ setTimeout(r, 400); });
			}
		}
		alert('Generation completed successfully.');
	}

	on(document, 'DOMContentLoaded', function(){
		var btn = qs('#icart-dl-ajax-start');
		on(btn, 'click', function(e){ e.preventDefault(); uploadAndStart(); });
	});
})();

