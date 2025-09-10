(function(){
	function hydrate(node){
		var postId = node.getAttribute('data-post-id');
		if(!postId){ return; }
		var endpoint = (window.MUNIM_GA4 && window.MUNIM_GA4.endpoint) || '';
		if(!endpoint){ return; }
		var url = endpoint + '?post_id=' + encodeURIComponent(postId);
		fetch(url, { credentials: 'same-origin', cache: 'no-store' })
			.then(function(r){ return r.ok ? r.json() : Promise.reject(); })
			.then(function(data){
				if(!data || typeof data.views !== 'number'){ return; }
				node.textContent = new Intl.NumberFormat().format(data.views);
				node.setAttribute('aria-busy','false');
			})
			.catch(function(){ node.setAttribute('aria-busy','false'); });
	}

	function init(){
		var nodes = document.querySelectorAll('.munim-ga4-views[data-post-id]');
		if(!nodes || !nodes.length){ return; }
		nodes.forEach(hydrate);
	}

	if('requestIdleCallback' in window){
		window.requestIdleCallback(init, { timeout: 2000 });
	}else{
		window.addEventListener('load', function(){ setTimeout(init, 0); });
	}
})();

