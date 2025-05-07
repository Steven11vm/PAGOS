// Common JavaScript functions

// Show confirmation dialog
function confirmAction(message) {
  return confirm(message)
}

// Format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat("es-CO", {
    style: "currency",
    currency: "COP",
    minimumFractionDigits: 2,
  }).format(amount)
}

// Toggle password visibility
function togglePasswordVisibility(inputId, iconId) {
  const input = document.getElementById(inputId)
  const icon = document.getElementById(iconId)

  if (input.type === "password") {
    input.type = "text"
    icon.classList.remove("fa-eye")
    icon.classList.add("fa-eye-slash")
  } else {
    input.type = "password"
    icon.classList.remove("fa-eye-slash")
    icon.classList.add("fa-eye")
  }
}

// Display flash messages
document.addEventListener("DOMContentLoaded", () => {
  // Auto-hide alerts after 5 seconds
  setTimeout(() => {
    const alerts = document.querySelectorAll(".alert:not(.alert-permanent)")
    alerts.forEach((alert) => {
      // Ensure bootstrap is available
      if (typeof bootstrap !== "undefined") {
        const bsAlert = new bootstrap.Alert(alert)
        bsAlert.close()
      } else {
        console.warn("Bootstrap is not defined. Ensure it is properly loaded.")
        // Optionally, remove the alert manually if bootstrap is not available
        alert.remove()
      }
    })
  }, 5000)
})
