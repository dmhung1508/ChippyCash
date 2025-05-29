document.addEventListener("DOMContentLoaded", () => {
  console.log("🚀 Initializing Dark Mode System...")
  
  // Simple Dark Mode Toggle
  const darkModeBtn = document.getElementById('darkModeToggle')
  const body = document.body
  
  // Check saved theme
  const savedTheme = localStorage.getItem('darkMode')
  let isDarkMode = savedTheme === 'true'
  
  // Apply initial theme
  if (isDarkMode) {
    body.classList.add('dark-mode')
    updateButton(true)
  }
  
  // Toggle function
  function toggleDarkMode() {
    isDarkMode = !isDarkMode
    
    if (isDarkMode) {
      body.classList.add('dark-mode')
    } else {
      body.classList.remove('dark-mode')
    }
    
    updateButton(isDarkMode)
    localStorage.setItem('darkMode', isDarkMode.toString())
    
    console.log(`Theme switched to: ${isDarkMode ? 'dark' : 'light'}`)
  }
  
  // Update button icon
  function updateButton(dark) {
    if (darkModeBtn) {
      const icon = darkModeBtn.querySelector('i')
      icon.className = dark ? 'fas fa-sun' : 'fas fa-moon'
    }
  }
  
  // Add click event
  if (darkModeBtn) {
    darkModeBtn.addEventListener('click', toggleDarkMode)
    console.log("✅ Dark mode toggle ready!")
  } else {
    console.error("❌ Dark mode button not found!")
  }

  // Initialize other components
  setupTransactionModals()
  setupCategoryModals() 
  setupChatbot()
  setupDropdown()
})

// Modal Management
const openModal = (modalId) => {
  const modal = document.getElementById(modalId)
  if (modal) {
    modal.style.display = 'block'
    document.body.style.overflow = 'hidden'
  }
}

const closeModal = (modalId) => {
  const modal = document.getElementById(modalId)
  if (modal) {
    modal.style.display = 'none'
    document.body.style.overflow = 'auto'
  }
}

// Transaction Functions
const setupTransactionModals = () => {
  console.log("Setting up transaction modals...")
  // Add transaction modal logic here
}

const setupCategoryModals = () => {
  console.log("Setting up category modals...")
  // Add category modal logic here  
}

const setupChatbot = () => {
  console.log("Setting up chatbot...")
  // Add chatbot logic here
}

// Dropdown Menu Handler
const setupDropdown = () => {
  const dropdownToggle = document.querySelector('.dropdown-toggle')
  const dropdownMenu = document.querySelector('.dropdown-menu')
  
  if (dropdownToggle && dropdownMenu) {
    // Toggle dropdown on click
    dropdownToggle.addEventListener('click', (e) => {
      e.preventDefault()
      e.stopPropagation()
      
      const isVisible = dropdownMenu.style.display === 'block'
      dropdownMenu.style.display = isVisible ? 'none' : 'block'
    })
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
        dropdownMenu.style.display = 'none'
      }
    })
    
    // Close dropdown on escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        dropdownMenu.style.display = 'none'
      }
    })
    
    console.log("✅ Dropdown menu ready!")
  } else {
    console.log("ℹ️ Dropdown not found (user not logged in)")
  }
}

