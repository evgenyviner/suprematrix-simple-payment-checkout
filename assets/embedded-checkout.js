(async function () {
    const container = document.querySelector("#secwp");
    if (!container) return;
  
    if (!window.secwp?.publishableKey) {
      container.innerHTML = "Stripe publishable key not configured.";
      return;
    }
  
    async function fetchClientSecret() {
      const res = await fetch(window.secwp.createSessionUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({}) // nothing needed since price is fixed server-side
      });
  
      const data = await res.json();
      if (!res.ok) throw new Error(data?.error || "Failed to create session");
      return data.clientSecret;
    }
  
    try {
      const stripe = Stripe(window.secwp.publishableKey);
      const clientSecret = await fetchClientSecret();
  
      const checkout = await stripe.initEmbeddedCheckout({ clientSecret });
      checkout.mount("#secwp");
    } catch (e) {
      console.error(e);
      container.innerHTML = "Could not load checkout. Please try again.";
    }
  })();
  