document.addEventListener("DOMContentLoaded", () => {
  // Theme management
  const body = document.body
  const savedTheme = localStorage.getItem("theme")
  if (savedTheme === "dark") body.classList.add("dark-mode")

  // Theme toggle button
  const themeToggle = document.getElementById("themeToggle")
  if (themeToggle) {
    // Tìm icon trong nút
    const icon = themeToggle.querySelector('i')
    if (icon) {
      // Cập nhật icon dựa trên theme hiện tại
      icon.className = body.classList.contains("dark-mode") ? 'fas fa-sun' : 'fas fa-moon'
    }

    themeToggle.addEventListener("click", () => {
      body.classList.toggle("dark-mode")
      const isDark = body.classList.contains("dark-mode")
      localStorage.setItem("theme", isDark ? "dark" : "light")
      
      // Chỉ thay đổi className của icon, không dùng innerHTML
      if (icon) {
        icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon'
      }
    })
  }

  // Dropdown menu - Enhanced version
  document.querySelectorAll(".dropdown-toggle").forEach((toggle) => {
    toggle.addEventListener("click", (e) => {
      e.preventDefault()
      e.stopPropagation() // Ngăn event bubbling
      
      const dropdown = toggle.nextElementSibling
      if (dropdown) {
        // Đóng tất cả dropdown khác trước
        document.querySelectorAll(".dropdown-menu").forEach((otherDropdown) => {
          if (otherDropdown !== dropdown && otherDropdown.style.display === "block") {
            otherDropdown.style.display = "none"
          }
        })
        
        // Toggle dropdown hiện tại
        const isVisible = dropdown.style.display === "block"
        dropdown.style.display = isVisible ? "none" : "block"
      }
    })
  })

  // Close dropdowns when clicking outside - Enhanced
  document.addEventListener("click", (e) => {
    // Kiểm tra xem click có phải trên dropdown toggle hay dropdown menu không
    if (!e.target.closest(".dropdown-toggle") && !e.target.closest(".dropdown-menu")) {
      document.querySelectorAll(".dropdown-menu").forEach((dropdown) => {
        if (dropdown.style.display === "block") {
          dropdown.style.display = "none"
        }
      })
    }
  })

  // Tab navigation - Enhanced for categories page
  const tabButtons = document.querySelectorAll(".tab-button, .magical-tab-button")
  tabButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const tabId = button.dataset.tab
      
      // Remove active class from all buttons and panes
      document.querySelectorAll(".tab-button, .magical-tab-button").forEach((btn) => {
        btn.classList.remove("active")
        // Reset tất cả styles về trạng thái inactive
        btn.style.background = "transparent"
        btn.style.color = "var(--secondary-color)"
        btn.style.boxShadow = "none"
        btn.style.transform = "translateY(0) scale(1)"
      })
      
      document.querySelectorAll(".tab-pane").forEach((pane) => {
        pane.classList.remove("active")
        pane.style.display = "none"
        pane.style.opacity = "0"
        pane.style.transform = "translateY(10px)"
      })
      
      // Add active class to clicked button and corresponding pane
      button.classList.add("active")
      button.style.background = "var(--card-background)"
      button.style.color = "var(--primary-color)"
      button.style.boxShadow = "0 4px 16px rgba(0,0,0,0.15)"
      button.style.transform = "translateY(-2px) scale(1.02)"
      
      const tabPane = document.getElementById(tabId)
      if (tabPane) {
        tabPane.classList.add("active")
        tabPane.style.display = "block"
        
        // Smooth transition animation
        setTimeout(() => {
          tabPane.style.opacity = "1"
          tabPane.style.transform = "translateY(0)"
        }, 50)
      }
    })
    
    // Improved hover effects để không conflict với active state
    button.addEventListener("mouseenter", () => {
      if (!button.classList.contains("active")) {
        button.style.background = "rgba(255,255,255,0.7)"
        button.style.color = "var(--primary-color)"
        button.style.transform = "translateY(-2px) scale(1.02)"
        button.style.boxShadow = "0 4px 16px rgba(0,0,0,0.1)"
      }
    })
    
    button.addEventListener("mouseleave", () => {
      if (!button.classList.contains("active")) {
        button.style.background = "transparent"
        button.style.color = "var(--secondary-color)"
        button.style.transform = "translateY(0) scale(1)"
        button.style.boxShadow = "none"
      }
    })
  })

  // Initialize first tab as active if none is active - Enhanced
  const activeTabButton = document.querySelector(".tab-button.active, .magical-tab-button.active")
  if (!activeTabButton) {
    const firstTabButton = document.querySelector(".tab-button, .magical-tab-button")
    const firstTabPane = document.querySelector(".tab-pane")
    if (firstTabButton && firstTabPane) {
      firstTabButton.classList.add("active")
      firstTabButton.style.background = "var(--card-background)"
      firstTabButton.style.color = "var(--primary-color)"
      firstTabButton.style.boxShadow = "0 4px 16px rgba(0,0,0,0.15)"
      firstTabButton.style.transform = "translateY(-2px) scale(1.02)"
      firstTabPane.classList.add("active")
      firstTabPane.style.display = "block"
      firstTabPane.style.opacity = "1"
      firstTabPane.style.transform = "translateY(0)"
    }
  } else {
    // Nếu đã có active tab, đảm bảo styles đúng
    const activePane = document.getElementById(activeTabButton.dataset.tab)
    if (activePane) {
      activePane.style.opacity = "1"
      activePane.style.transform = "translateY(0)"
    }
  }

  // Flash messages
  const flashMessages = document.querySelectorAll(".alert")
  if (flashMessages.length > 0) {
    setTimeout(() => {
      flashMessages.forEach((message) => {
        message.style.opacity = "0"
        setTimeout(() => (message.style.display = "none"), 500)
      })
    }, 5000)
  }

  // Modal management
  const modals = document.querySelectorAll(".modal")

  // Generic modal functions
  const openModal = (modalId) => {
    console.log(`Attempting to open modal: ${modalId}`)
    const modal = document.getElementById(modalId)
    if (modal) {
      console.log(`Modal ${modalId} found, opening...`)
      modal.style.display = "block"
      document.body.style.overflow = "hidden"
    } else {
      console.error(`Modal ${modalId} not found!`)
    }
  }

  const closeModal = (modalId) => {
    const modal = document.getElementById(modalId)
    if (modal) {
      modal.style.display = "none"
      document.body.style.overflow = "auto"
    }
  }

  // Close all modals when clicking outside
  window.addEventListener("click", (e) => {
    modals.forEach((modal) => {
      if (e.target === modal) {
        modal.style.display = "none"
        document.body.style.overflow = "auto"
      }
    })
  })

  // Close buttons for modals
  document.querySelectorAll(".close-modal, .cancel-modal").forEach((btn) => {
    btn.addEventListener("click", () => {
      const modal = btn.closest(".modal")
      if (modal) {
        modal.style.display = "none"
        document.body.style.overflow = "auto"
      }
    })
  })

  // Transaction modals
  const setupTransactionModals = () => {
    // Add transaction button
    const addTransactionBtn = document.getElementById("addTransactionBtn")
    const emptyAddTransactionBtn = document.getElementById("emptyAddTransactionBtn")

    if (addTransactionBtn) {
      addTransactionBtn.addEventListener("click", () => openModal("addTransactionModal"))
    }

    if (emptyAddTransactionBtn) {
      emptyAddTransactionBtn.addEventListener("click", () => openModal("addTransactionModal"))
    }

    // Edit transaction buttons
    const editTransactionBtns = document.querySelectorAll(".edit-transaction-btn")
    console.log(`Found ${editTransactionBtns.length} edit transaction buttons`)
    if (editTransactionBtns.length > 0) {
      editTransactionBtns.forEach((btn) => {
        btn.addEventListener("click", (e) => {
          e.stopPropagation()
          console.log("Edit transaction button clicked")

          // Get transaction data from button attributes
          const id = btn.getAttribute("data-id")
          const amount = btn.getAttribute("data-amount")
          const description = btn.getAttribute("data-description")
          const type = btn.getAttribute("data-type")
          const category = btn.getAttribute("data-category")
          const date = btn.getAttribute("data-date")

          // Fill the edit form
          const idInput = document.getElementById("edit-transaction-id")
          const amountInput = document.getElementById("edit-amount")
          const descriptionInput = document.getElementById("edit-description")
          const dateInput = document.getElementById("edit-date")
          const typeSelect = document.getElementById("edit-type")
          const categorySelect = document.getElementById("edit-category")

          if (idInput) idInput.value = id || ""
          if (amountInput) amountInput.value = amount || ""
          if (descriptionInput) descriptionInput.value = description || ""
          if (dateInput) dateInput.value = date || ""
          if (typeSelect) typeSelect.value = type || "expense"

          // Show/hide appropriate category options
          if (typeSelect && categorySelect) {
            updateCategoryVisibility(typeSelect, "edit-income-categories", "edit-expense-categories", "edit-category")
            categorySelect.value = category || ""
          }

          // Open modal
          console.log("About to open editTransactionModal")
          openModal("editTransactionModal")
        })
      })
    }

    // Update category options based on transaction type
    const updateCategoryVisibility = (typeSelect, incomeId, expenseId, categoryId) => {
      const incomeCategories = document.getElementById(incomeId)
      const expenseCategories = document.getElementById(expenseId)
      const categorySelect = document.getElementById(categoryId)

      if (!typeSelect || !incomeCategories || !expenseCategories || !categorySelect) return

      const isIncome = typeSelect.value === "income"
      incomeCategories.style.display = isIncome ? "" : "none"
      expenseCategories.style.display = isIncome ? "none" : ""

      // Select first option of visible category group
      const visibleGroup = isIncome ? incomeCategories : expenseCategories
      const firstOption = visibleGroup.querySelector("option")
      if (firstOption) categorySelect.value = firstOption.value
    }

    // Add event listeners for type selects
    const typeSelects = [
      {
        select: document.getElementById("type"),
        income: "income-categories",
        expense: "expense-categories",
        category: "category",
      },
      {
        select: document.getElementById("edit-type"),
        income: "edit-income-categories",
        expense: "edit-expense-categories",
        category: "edit-category",
      },
    ]

    typeSelects.forEach((item) => {
      if (item.select) {
        // Initialize
        updateCategoryVisibility(item.select, item.income, item.expense, item.category)

        // Add change listener
        item.select.addEventListener("change", () => {
          updateCategoryVisibility(item.select, item.income, item.expense, item.category)
        })
      }
    })
  }

  // Category modals
  const setupCategoryModals = () => {
    // Add category buttons
    const addCategoryBtn = document.getElementById("addCategoryBtn")
    const emptyAddIncomeCategoryBtn = document.getElementById("emptyAddIncomeCategoryBtn")
    const emptyAddExpenseCategoryBtn = document.getElementById("emptyAddExpenseCategoryBtn")
    const modalTypeSelect = document.getElementById("modal-type")

    if (addCategoryBtn) {
      addCategoryBtn.addEventListener("click", () => openModal("addCategoryModal"))
    }

    if (emptyAddIncomeCategoryBtn) {
      emptyAddIncomeCategoryBtn.addEventListener("click", () => {
        openModal("addCategoryModal")
        if (modalTypeSelect) modalTypeSelect.value = "income"
      })
    }

    if (emptyAddExpenseCategoryBtn) {
      emptyAddExpenseCategoryBtn.addEventListener("click", () => {
        openModal("addCategoryModal")
        if (modalTypeSelect) modalTypeSelect.value = "expense"
      })
    }

    // Edit category buttons
    const editCategoryBtns = document.querySelectorAll(".edit-category-btn")
    console.log(`Found ${editCategoryBtns.length} edit category buttons`)
    if (editCategoryBtns.length > 0) {
      editCategoryBtns.forEach((btn) => {
        btn.addEventListener("click", (e) => {
          e.stopPropagation() // Prevent card click event
          console.log("Edit category button clicked")
          
          const categoryId = btn.getAttribute("data-id")
          console.log("Category ID:", categoryId)
          
          // Tìm cả .category-card và .magical-category-card
          let categoryCard = document.querySelector(`.category-card[data-id="${categoryId}"]`)
          if (!categoryCard) {
            categoryCard = document.querySelector(`.magical-category-card[data-id="${categoryId}"]`)
          }
          
          console.log("Category card found:", !!categoryCard)
          if (!categoryCard) return

          const categoryData = categoryCard.querySelector(".category-data")
          console.log("Category data found:", !!categoryData)
          if (!categoryData) return

          // Fill the edit form with category data
          const idInput = document.getElementById("edit-category-id")
          const nameInput = document.getElementById("edit-name")
          const descriptionInput = document.getElementById("edit-description")
          const typeSelect = document.getElementById("edit-type")
          const usageText = document.getElementById("edit-usage-text")
          const usageHint = document.getElementById("edit-usage-hint")

          if (idInput) idInput.value = categoryData.getAttribute("data-id") || ""
          if (nameInput) nameInput.value = categoryData.getAttribute("data-name") || ""
          if (descriptionInput) descriptionInput.value = categoryData.getAttribute("data-description") || ""
          if (typeSelect) typeSelect.value = categoryData.getAttribute("data-type") || "expense"

          console.log("Form filled with data:", {
            id: categoryData.getAttribute("data-id"),
            name: categoryData.getAttribute("data-name"),
            type: categoryData.getAttribute("data-type")
          })

          // Update usage info
          const usageCount = Number.parseInt(categoryData.getAttribute("data-usage") || "0", 10)

          if (usageText) {
            if (usageCount > 0) {
              usageText.textContent = `Thể loại này đang được sử dụng trong ${usageCount} giao dịch.`
              if (usageHint) usageHint.style.display = "block"
            } else {
              usageText.textContent = "Thể loại này chưa được sử dụng trong bất kỳ giao dịch nào."
              if (usageHint) usageHint.style.display = "none"
            }
          }

          console.log("About to open editCategoryModal")
          openModal("editCategoryModal")
        })
      })
    }

    // Make category cards clickable to edit
    const categoryCards = document.querySelectorAll(".category-card, .magical-category-card")
    console.log(`Found ${categoryCards.length} category cards`)
    if (categoryCards.length > 0) {
      categoryCards.forEach((card) => {
        card.addEventListener("click", () => {
          const editBtn = card.querySelector(".edit-category-btn")
          if (editBtn) {
            console.log("Category card clicked, triggering edit button")
            // Trigger the edit button click event
            editBtn.click()
          }
        })
      })
    }
  }

  // Reset filter button
  const resetFilterBtn = document.getElementById("resetFilterBtn")
  if (resetFilterBtn) {
    resetFilterBtn.addEventListener("click", () => {
      window.location.href = "transactions.php"
    })
  }

  // Hàm đọc text bằng AI - Version đơn giản và ổn định
  const speakText = async (text, voiceType, buttonElement) => {
    if (!text || !text.trim()) {
      console.log("Không có text để đọc")
      return
    }

    console.log("🔊 Bắt đầu đọc text:", text.substring(0, 50) + "...")

    // Thay đổi giao diện nút khi đang xử lý
    const originalIcon = buttonElement.innerHTML
    const originalTitle = buttonElement.title
    buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'
    buttonElement.title = "Đang xử lý..."
    buttonElement.disabled = true
    buttonElement.style.opacity = "0.7"

    try {
      console.log("📡 Gọi API text-to-speech với:", { text: text.length + " ký tự", voiceType })

      const response = await fetch("http://127.0.0.1:8506/voice/stream", {
        method: "POST",
        headers: {
          "accept": "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          text: text,
          voice_type: voiceType
        })
      })

      console.log("📨 Response status:", response.status)

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }

      console.log("✅ Nhận phản hồi từ voice API, đang tải toàn bộ audio...")

      // Đổi icon sang downloading
      buttonElement.innerHTML = '<i class="fas fa-download fa-pulse"></i>'
      buttonElement.title = "Đang tải audio..."

      // Lấy toàn bộ dữ liệu audio trước khi phát
      const audioBlob = await response.blob()
      console.log("📦 Đã tải xong audio blob:", audioBlob.size, "bytes")

      // Tạo URL cho audio
      const audioUrl = URL.createObjectURL(audioBlob)
      const audio = new Audio(audioUrl)

      // Cập nhật giao diện khi bắt đầu phát
      buttonElement.innerHTML = '<i class="fas fa-pause"></i>'
      buttonElement.title = "Đang phát..."

      // Đảm bảo audio context được kích hoạt (cho một số browser)
      if (typeof audio.play === 'function') {
        try {
          await audio.play()
          console.log("▶️ Bắt đầu phát audio hoàn chỉnh")
        } catch (playError) {
          console.error("❌ Lỗi phát audio:", playError)
          throw playError
        }
      }

      // Xử lý khi audio kết thúc
      audio.onended = () => {
        console.log("🔇 Audio đã phát xong")
        buttonElement.innerHTML = originalIcon
        buttonElement.title = originalTitle
        buttonElement.disabled = false
        buttonElement.style.opacity = "1"
        URL.revokeObjectURL(audioUrl)
      }

      // Xử lý lỗi audio
      audio.onerror = (error) => {
        console.error("❌ Lỗi audio:", error)
        buttonElement.innerHTML = originalIcon
        buttonElement.title = originalTitle
        buttonElement.disabled = false
        buttonElement.style.opacity = "1"
        URL.revokeObjectURL(audioUrl)
      }

      // Xử lý khi audio bị pause/stop
      audio.onpause = () => {
        console.log("⏸️ Audio bị pause")
        buttonElement.innerHTML = originalIcon
        buttonElement.title = originalTitle
        buttonElement.disabled = false
        buttonElement.style.opacity = "1"
      }

    } catch (error) {
      console.error("❌ Lỗi khi gọi API text-to-speech:", error)
      
      // Hiển thị thông báo lỗi ngắn gọn
      const errorDiv = document.createElement("div")
      errorDiv.style.background = "#fed7d7"
      errorDiv.style.color = "#c53030"
      errorDiv.style.padding = "6px 10px"
      errorDiv.style.borderRadius = "4px"
      errorDiv.style.fontSize = "0.8rem"
      errorDiv.style.marginTop = "4px"
      errorDiv.style.border = "1px solid #feb2b2"
      errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Không thể đọc tin nhắn'
      
      buttonElement.parentElement.appendChild(errorDiv)
      
      // Tự động xóa thông báo lỗi sau 3 giây
      setTimeout(() => {
        if (errorDiv.parentElement) {
          errorDiv.remove()
        }
      }, 3000)
      
    } finally {
      // Đảm bảo khôi phục giao diện nút nếu có lỗi
      if (buttonElement.innerHTML.includes('spinner') || buttonElement.innerHTML.includes('download')) {
        buttonElement.innerHTML = originalIcon
        buttonElement.title = originalTitle
        buttonElement.disabled = false
        buttonElement.style.opacity = "1"
      }
    }
  }

  // Thêm hàm vào global scope để có thể truy cập từ HTML
  window.speakText = speakText

  // Chatbot functionality - FULL FEATURED VERSION
  const setupChatbot = () => {
    const qaMessages = document.getElementById("qaMessages")
    const questionInput = document.getElementById("questionInput")
    const sendQuestion = document.getElementById("sendQuestion")
    const roleSelect = document.getElementById("chatRoleSelect")
    const transactionsContainer = document.getElementById("transactions")
    const recentTransactionsTable = transactionsContainer
      ? transactionsContainer.querySelector(".transactions-table")
      : null

    if (!qaMessages || !questionInput || !sendQuestion) return

    // Update category options based on transaction type
    const updateCategoryVisibility = (typeSelect, incomeId, expenseId, categoryId) => {
      const incomeCategories = document.getElementById(incomeId)
      const expenseCategories = document.getElementById(expenseId)
      const categorySelect = document.getElementById(categoryId)

      if (!typeSelect || !incomeCategories || !expenseCategories || !categorySelect) return

      const isIncome = typeSelect.value === "income"
      incomeCategories.style.display = isIncome ? "" : "none"
      expenseCategories.style.display = isIncome ? "none" : ""

      // Select first option of visible category group
      const visibleGroup = isIncome ? incomeCategories : expenseCategories
      const firstOption = visibleGroup.querySelector("option")
      if (firstOption) categorySelect.value = firstOption.value
    }

    // Xóa các tin nhắn mẫu
    document.querySelectorAll(".question-item").forEach((item) => {
      item.remove()
    })

    // Biến để theo dõi trạng thái xử lý
    let isProcessing = false
    // Biến lưu trữ giao dịch hiện tại
    let currentTransactions = []

    // Load saved role from localStorage
    const savedRole = localStorage.getItem("chatRole")
    if (savedRole && roleSelect) {
      roleSelect.value = savedRole
    }

    // Save role when changed
    if (roleSelect) {
      roleSelect.addEventListener("change", () => {
        localStorage.setItem("chatRole", roleSelect.value)
      })
    }

    // Suggestion buttons functionality
    const suggestionButtons = document.querySelectorAll('.suggestion-btn')
    suggestionButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        const text = this.textContent.trim()
        questionInput.value = text.substring(2) // Remove emoji
        questionInput.focus()
      })
    })

    // Enhanced message adding function
    const addMessageToChat = (message, sender) => {
      const messageDiv = document.createElement("div")
      messageDiv.className = `message ${sender}`
      messageDiv.style.display = "flex"
      messageDiv.style.alignItems = "flex-start"
      messageDiv.style.gap = "10px"
      messageDiv.style.marginBottom = "12px"
      
      if (sender === "user") {
        messageDiv.style.flexDirection = "row-reverse"
      }

      const avatar = document.createElement("div")
      avatar.className = "message-avatar"
      avatar.style.width = "32px"
      avatar.style.height = "32px"
      avatar.style.borderRadius = "50%"
      avatar.style.display = "flex"
      avatar.style.alignItems = "center"
      avatar.style.justifyContent = "center"
      avatar.style.flexShrink = "0"
      avatar.style.color = "white"
      avatar.style.fontSize = "0.8rem"
      
      if (sender === "user") {
        avatar.style.background = "var(--primary-color)"
        avatar.innerHTML = '<i class="fas fa-user"></i>'
      } else {
        avatar.style.background = "var(--accent-color)"
        avatar.innerHTML = '<i class="fas fa-robot"></i>'
      }

      // Container cho tin nhắn và các nút
      const messageContainer = document.createElement("div")
      messageContainer.style.display = "flex"
      messageContainer.style.flexDirection = "column"
      messageContainer.style.gap = "6px"
      messageContainer.style.maxWidth = "80%"

      const content = document.createElement("div")
      content.className = "message-content"
      content.style.padding = "12px 16px"
      content.style.borderRadius = "8px"
      content.style.lineHeight = "1.4"
      content.style.fontSize = "0.9rem"
      content.style.background = "var(--card-background)"
      content.style.color = "var(--primary-color)"
      content.style.boxShadow = "0 1px 3px rgba(0,0,0,0.1)"
      content.style.border = "1px solid var(--border-color)"

      // Handle line breaks in message
      const formattedMessage = message.split("\n").map((line) => {
        const span = document.createElement("span")
        span.textContent = line
        return span
      })

      content.innerHTML = ""
      formattedMessage.forEach((span, index) => {
        content.appendChild(span)
        if (index < formattedMessage.length - 1) {
          content.appendChild(document.createElement("br"))
        }
      })

      if (sender === "user") {
        content.style.background = "var(--accent-color)"
        content.style.color = "white"
        content.style.borderTopRightRadius = "3px"
        content.style.border = "1px solid var(--accent-color)"
      } else {
        content.style.borderTopLeftRadius = "3px"
      }

      messageContainer.appendChild(content)

      // Thêm nút đọc text cho tin nhắn bot
      if (sender === "bot") {
        const actionsDiv = document.createElement("div")
        actionsDiv.style.display = "flex"
        actionsDiv.style.gap = "6px"
        actionsDiv.style.alignItems = "center"

        const speakButton = document.createElement("button")
        speakButton.className = "speak-button"
        speakButton.innerHTML = '<i class="fas fa-volume-up"></i>'
        speakButton.title = "Đọc tin nhắn"
        speakButton.style.background = "var(--hover-color)"
        speakButton.style.border = "1px solid var(--border-color)"
        speakButton.style.borderRadius = "6px"
        speakButton.style.padding = "6px 8px"
        speakButton.style.cursor = "pointer"
        speakButton.style.fontSize = "0.8rem"
        speakButton.style.color = "var(--secondary-color)"
        speakButton.style.transition = "all 0.2s ease"
        speakButton.style.display = "flex"
        speakButton.style.alignItems = "center"
        speakButton.style.gap = "4px"

        speakButton.onmouseover = () => {
          speakButton.style.background = "var(--accent-color)"
          speakButton.style.color = "white"
          speakButton.style.borderColor = "var(--accent-color)"
        }
        
        speakButton.onmouseout = () => {
          speakButton.style.background = "var(--hover-color)"
          speakButton.style.color = "var(--secondary-color)"
          speakButton.style.borderColor = "var(--border-color)"
        }

        speakButton.addEventListener("click", () => {
          const selectedRole = roleSelect ? roleSelect.value : "Trợ lý thông minh"
          speakText(message, selectedRole, speakButton)
        })

        actionsDiv.appendChild(speakButton)
        messageContainer.appendChild(actionsDiv)
      }

      messageDiv.appendChild(avatar)
      messageDiv.appendChild(messageContainer)
      qaMessages.appendChild(messageDiv)

      // Scroll to bottom
      qaMessages.scrollTop = qaMessages.scrollHeight

      return messageDiv
    }

    const showTypingIndicator = () => {
      const typingDiv = document.createElement("div")
      typingDiv.className = "message bot typing-indicator"
      typingDiv.id = "typing-indicator"
      typingDiv.style.display = "flex"
      typingDiv.style.alignItems = "flex-start"
      typingDiv.style.gap = "10px"
      typingDiv.style.marginBottom = "12px"
      
      const avatar = document.createElement("div")
      avatar.className = "message-avatar"
      avatar.style.width = "32px"
      avatar.style.height = "32px"
      avatar.style.borderRadius = "50%"
      avatar.style.background = "var(--accent-color)"
      avatar.style.display = "flex"
      avatar.style.alignItems = "center"
      avatar.style.justifyContent = "center"
      avatar.style.flexShrink = "0"
      avatar.style.color = "white"
      avatar.style.fontSize = "0.8rem"
      avatar.innerHTML = '<i class="fas fa-robot"></i>'
      
      const content = document.createElement("div")
      content.className = "message-content"
      content.style.background = "var(--card-background)"
      content.style.padding = "12px 16px"
      content.style.borderRadius = "8px"
      content.style.borderTopLeftRadius = "3px"
      content.style.boxShadow = "0 1px 3px rgba(0,0,0,0.1)"
      content.style.border = "1px solid var(--border-color)"
      
      const loadingDiv = document.createElement("div")
      loadingDiv.className = "message-loading"
      loadingDiv.style.display = "flex"
      loadingDiv.style.alignItems = "center"
      loadingDiv.style.gap = "4px"
      loadingDiv.style.padding = "4px 0"
      
      for (let i = 0; i < 3; i++) {
        const dot = document.createElement("span")
        dot.style.width = "5px"
        dot.style.height = "5px"
        dot.style.borderRadius = "50%"
        dot.style.background = "var(--secondary-color)"
        dot.style.animation = "bounce 1.4s ease-in-out infinite both"
        dot.style.animationDelay = `${-0.32 + i * 0.16}s`
        loadingDiv.appendChild(dot)
      }
      
      content.appendChild(loadingDiv)
      typingDiv.appendChild(avatar)
      typingDiv.appendChild(content)
      qaMessages.appendChild(typingDiv)
      qaMessages.scrollTop = qaMessages.scrollHeight
    }

    const removeTypingIndicator = () => {
      const typingIndicator = document.getElementById("typing-indicator")
      if (typingIndicator) {
        typingIndicator.remove()
      }
    }

    // Cập nhật giao diện người dùng với giao dịch mới
    const updateUIWithNewTransactions = (transactions) => {
      if (!transactionsContainer || !recentTransactionsTable || !transactions || transactions.length === 0) return

      // Kiểm tra xem đã có bảng giao dịch chưa
      let tbody = recentTransactionsTable.querySelector("tbody")

      // Nếu chưa có bảng, tạo bảng mới
      if (!tbody) {
        const table = document.createElement("table")
        const thead = document.createElement("thead")
        thead.innerHTML = `
          <tr>
            <th>Ngày</th>
            <th>Mô tả</th>
            <th>Danh mục</th>
            <th>Loại</th>
            <th>Số tiền</th>
            <th>Thao tác</th>
          </tr>
        `
        tbody = document.createElement("tbody")
        table.appendChild(thead)
        table.appendChild(tbody)
        recentTransactionsTable.appendChild(table)
      }

      // Xóa thông báo trống nếu có
      const emptyState = transactionsContainer.querySelector(".empty-state")
      if (emptyState) {
        emptyState.style.display = "none"
      }

      // Thêm giao dịch mới vào đầu bảng
      transactions.forEach((transaction) => {
        const today = new Date()
        const formattedDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`

        const tr = document.createElement("tr")
        tr.innerHTML = `
          <td>${today.getDate()}/${today.getMonth() + 1}/${today.getFullYear()}</td>
          <td>${transaction.name}</td>
          <td>${transaction.category || "Chung"}</td>
          <td>
            <span class="badge ${transaction.type === "income" ? "income" : "expense"}">
              ${transaction.type === "income" ? "Thu nhập" : "Chi tiêu"}
            </span>
          </td>
          <td class="amount ${transaction.type === "income" ? "positive" : "negative"}">
            ${new Intl.NumberFormat("vi-VN").format(transaction.amount)}₫
          </td>
          <td class="actions">
            <button class="btn-icon edit edit-transaction-btn" title="Chỉnh sửa" 
              data-id=""
              data-amount="${transaction.amount}"
              data-description="${transaction.name}"
              data-type="${transaction.type === "income" ? "income" : "expense"}"
              data-category="${transaction.category || ""}"
              data-date="${formattedDate}">
              <i class="fas fa-edit"></i>
            </button>
            <a href="transactions.php?delete=" class="btn-icon delete" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa giao dịch này?');">
              <i class="fas fa-trash"></i>
            </a>
          </td>
        `

        // Thêm vào đầu bảng
        if (tbody.firstChild) {
          tbody.insertBefore(tr, tbody.firstChild)
        } else {
          tbody.appendChild(tr)
        }

        // Thêm event listener cho nút edit mới tạo
        const editBtn = tr.querySelector('.edit-transaction-btn')
        if (editBtn) {
          console.log("Adding event listener to new edit button")
          editBtn.addEventListener('click', (e) => {
            e.stopPropagation()
            console.log("New edit transaction button clicked (from chat)")
            
            // Get transaction data from button attributes
            const id = editBtn.getAttribute("data-id")
            const amount = editBtn.getAttribute("data-amount")
            const description = editBtn.getAttribute("data-description")
            const type = editBtn.getAttribute("data-type")
            const category = editBtn.getAttribute("data-category")
            const date = editBtn.getAttribute("data-date")

            // Fill the edit form
            const idInput = document.getElementById("edit-transaction-id")
            const amountInput = document.getElementById("edit-amount")
            const descriptionInput = document.getElementById("edit-description")
            const dateInput = document.getElementById("edit-date")
            const typeSelect = document.getElementById("edit-type")
            const categorySelect = document.getElementById("edit-category")

            if (idInput) idInput.value = id || ""
            if (amountInput) amountInput.value = amount || ""
            if (descriptionInput) descriptionInput.value = description || ""
            if (dateInput) dateInput.value = date || ""
            if (typeSelect) typeSelect.value = type || "expense"

            // Show/hide appropriate category options
            if (typeSelect && categorySelect) {
              updateCategoryVisibility(typeSelect, "edit-income-categories", "edit-expense-categories", "edit-category")
              categorySelect.value = category || ""
            }

            // Open modal
            console.log("About to open editTransactionModal from chat")
            openModal("editTransactionModal")
          })
        }
      })

      // Cập nhật số liệu tài chính
      updateFinancialSummary(transactions)
    }

    // Cập nhật tổng quan tài chính
    const updateFinancialSummary = (transactions) => {
      if (!transactions || transactions.length === 0) return

      // Tìm các phần tử hiển thị số liệu tài chính
      const financeCards = document.querySelectorAll(".finance-card")
      if (financeCards.length < 3) return

      // Lấy giá trị hiện tại
      const currentBalance =
        Number.parseFloat(
          financeCards[0]
            .querySelector(".card-amount")
            .textContent.replace(/[^\d,-]/g, "")
            .replace(",", "."),
        ) || 0
      let currentIncome =
        Number.parseFloat(
          financeCards[1]
            .querySelector(".card-amount")
            .textContent.replace(/[^\d,-]/g, "")
            .replace(",", "."),
        ) || 0
      let currentExpense =
        Number.parseFloat(
          financeCards[2]
            .querySelector(".card-amount")
            .textContent.replace(/[^\d,-]/g, "")
            .replace(",", "."),
        ) || 0

      // Tính toán giá trị mới
      let newIncome = 0
      let newExpense = 0

      transactions.forEach((transaction) => {
        if (transaction.type === "income") {
          newIncome += Number.parseFloat(transaction.amount)
        } else {
          newExpense += Number.parseFloat(transaction.amount)
        }
      })

      // Cập nhật giá trị
      const newBalance = currentBalance + newIncome - newExpense
      currentIncome += newIncome
      currentExpense += newExpense

      // Cập nhật giao diện
      financeCards[0].querySelector(".card-amount").textContent =
        new Intl.NumberFormat("vi-VN").format(newBalance) + "₫"
      financeCards[0].querySelector(".card-amount").className =
        `card-amount ${newBalance >= 0 ? "positive" : "negative"}`

      financeCards[1].querySelector(".card-amount").textContent =
        new Intl.NumberFormat("vi-VN").format(currentIncome) + "₫"
      financeCards[2].querySelector(".card-amount").textContent =
        new Intl.NumberFormat("vi-VN").format(currentExpense) + "₫"
    }

    // Lưu giao dịch vào server
    const saveTransactions = async (transactions) => {
      if (!transactions || transactions.length === 0 || isProcessing) return

      isProcessing = true
      let savingMessage = null;

      try {
        // Hiển thị tin nhắn đang lưu
        savingMessage = addMessageToChat("Đang lưu giao dịch...", "bot")

        // Giới hạn số lượng giao dịch để tránh quá tải
        const transactionsToSave = transactions.slice(0, 5)

        // Debug: Log transactions trước khi save
        console.log("Transactions to save:", transactionsToSave)
        transactionsToSave.forEach((tx, index) => {
          console.log(`Transaction ${index + 1}:`, {
            name: tx.name,
            category: tx.category,
            amount: tx.amount,
            type: tx.type
          })
        })

        // Gửi đến server
        const response = await fetch("api/save-transactions.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ transactions: transactionsToSave }),
        })

        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`)
        }

        const data = await response.json()

        if (data.success) {
          // Cập nhật tin nhắn lưu
          if (savingMessage) {
            const content = savingMessage.querySelector(".message-content")
            if (content) content.textContent = "Đã lưu giao dịch thành công! ✅"
          }

          // Cập nhật giao diện người dùng
          updateUIWithNewTransactions(transactionsToSave)

          // Vô hiệu hóa nút sửa
          const editButtons = document.querySelectorAll(".edit-transactions-btn")
          editButtons.forEach((button) => {
            button.disabled = true
            button.textContent = "Đã lưu"
            button.style.backgroundColor = "#22c55e"
          })

          // Đóng modal nếu đang mở
          const editModal = document.getElementById("editTransactionsModal")
          if (editModal && editModal.style.display === "block") {
            editModal.style.display = "none"
            document.body.style.overflow = "auto"
          }

          // Gọi API xóa lịch sử chat
          const userId = document.getElementById("user-id")?.value || "user"
          fetch("http://127.0.0.1:8506/delete", {
            method: "POST",
            headers: {
              accept: "application/json",
              "Content-Type": "application/json",
            },
            body: JSON.stringify({ id_user: userId }),
          })
            .then((response) => {
              if (response.ok) {
                console.log("Lịch sử chat đã được xóa")
              } else {
                console.error("Không thể xóa lịch sử chat")
              }
            })
            .catch((error) => {
              console.error("Lỗi khi xóa lịch sử chat:", error)
            })
        } else {
          // Hiển thị tin nhắn lỗi
          if (savingMessage) {
            const content = savingMessage.querySelector(".message-content")
            if (content) content.textContent = "Lỗi: " + (data.message || "Không thể lưu giao dịch")
          }
        }
      } catch (error) {
        console.error("Error saving transactions:", error)
        addMessageToChat("Lỗi khi lưu giao dịch: " + error.message, "bot")
      } finally {
        isProcessing = false
      }
    }

    // Hiển thị popup chỉnh sửa nhiều giao dịch từ AI
    const showEditTransactionsModal = (transactions) => {
      if (!transactions || transactions.length === 0) return

      console.log("showEditTransactionsModal called with:", transactions)

      // Sử dụng modal mới cho multiple transactions
      const modal = document.getElementById("editMultipleTransactionsModal")
      if (!modal) {
        console.error("editMultipleTransactionsModal not found!")
        alert("Modal không tìm thấy!")
        return
      }

      // Lưu trữ giao dịch hiện tại
      window.currentEditingTransactions = [...transactions]

      // Tạo danh sách giao dịch
      const transactionsList = document.getElementById("transactions-list")
      if (!transactionsList) return

      transactionsList.innerHTML = ""

      transactions.forEach((transaction, index) => {
        const transactionCard = document.createElement("div")
        transactionCard.className = "transaction-edit-card"
        transactionCard.style.cssText = `
          background: var(--card-background);
          border: 1px solid var(--border-color);
          border-radius: 8px;
          padding: 16px;
          margin-bottom: 12px;
          transition: all 0.2s ease;
          box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        `

        transactionCard.innerHTML = `
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="width:24px;height:24px;border-radius:50%;background:${transaction.type === 'income' ? '#dcfce7' : '#fee2e2'};display:flex;align-items:center;justify-content:center;">
                <i class="fas ${transaction.type === 'income' ? 'fa-plus' : 'fa-minus'}" 
                   style="color:${transaction.type === 'income' ? '#16a34a' : '#dc2626'};font-size:0.7rem;"></i>
              </div>
              <span style="color:var(--primary-color);font-size:0.9rem;font-weight:600;">
                #${index + 1}
              </span>
            </div>
            <button class="remove-transaction-btn" data-index="${index}" 
                    style="background:none;color:#dc2626;border:none;padding:4px;cursor:pointer;border-radius:4px;transition:all 0.2s ease;width:28px;height:28px;display:flex;align-items:center;justify-content:center;"
                    onmouseover="this.style.background='#fee2e2'" 
                    onmouseout="this.style.background='none'" 
                    title="Xóa giao dịch">
              <i class="fas fa-times" style="font-size:0.8rem;"></i>
            </button>
          </div>
          
          <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px;margin-bottom:12px;">
            <div>
              <label style="display:block;color:var(--secondary-color);font-size:0.75rem;margin-bottom:4px;font-weight:500;">Mô tả</label>
              <input type="text" class="transaction-description" data-index="${index}" 
                     value="${transaction.name}" placeholder="Nhập mô tả giao dịch"
                     style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.85rem;transition:all 0.2s ease;box-sizing:border-box;"
                     onfocus="this.style.borderColor='var(--accent-color)';this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'" 
                     onblur="this.style.borderColor='var(--border-color)';this.style.boxShadow='none'">
            </div>
            <div>
              <label style="display:block;color:var(--secondary-color);font-size:0.75rem;margin-bottom:4px;font-weight:500;">Số tiền</label>
              <input type="number" class="transaction-amount" data-index="${index}" 
                     value="${transaction.amount}" placeholder="0"
                     style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.85rem;transition:all 0.2s ease;box-sizing:border-box;"
                     onfocus="this.style.borderColor='var(--accent-color)';this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'" 
                     onblur="this.style.borderColor='var(--border-color)';this.style.boxShadow='none'">
            </div>
          </div>
          
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
              <label style="display:block;color:var(--secondary-color);font-size:0.75rem;margin-bottom:4px;font-weight:500;">Loại giao dịch</label>
              <select class="transaction-type" data-index="${index}" 
                      style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.85rem;transition:all 0.2s ease;box-sizing:border-box;cursor:pointer;"
                      onfocus="this.style.borderColor='var(--accent-color)';this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'" 
                      onblur="this.style.borderColor='var(--border-color)';this.style.boxShadow='none'">
                <option value="expense" ${transaction.type !== 'income' ? 'selected' : ''}>Chi tiêu</option>
                <option value="income" ${transaction.type === 'income' ? 'selected' : ''}>Thu nhập</option>
              </select>
            </div>
            <div>
              <label style="display:block;color:var(--secondary-color);font-size:0.75rem;margin-bottom:4px;font-weight:500;">Thể loại</label>
              <input type="text" class="transaction-category" data-index="${index}" 
                     value="${transaction.category || 'Chung'}" placeholder="Thể loại"
                     style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.85rem;transition:all 0.2s ease;box-sizing:border-box;"
                     onfocus="this.style.borderColor='var(--accent-color)';this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'" 
                     onblur="this.style.borderColor='var(--border-color)';this.style.boxShadow='none'">
            </div>
          </div>
        `

        transactionsList.appendChild(transactionCard)
      })

      // Event listeners cho các nút xóa
      document.querySelectorAll(".remove-transaction-btn").forEach(btn => {
        btn.addEventListener("click", (e) => {
          const index = parseInt(e.target.closest(".remove-transaction-btn").getAttribute("data-index"))
          window.currentEditingTransactions.splice(index, 1)
          
          if (window.currentEditingTransactions.length === 0) {
            modal.style.display = "none"
            document.body.style.overflow = "auto"
          } else {
            showEditTransactionsModal(window.currentEditingTransactions)
          }
        })
      })

      // Event listeners cho form inputs
      document.querySelectorAll(".transaction-description, .transaction-amount, .transaction-type, .transaction-category").forEach(input => {
        input.addEventListener("input", (e) => {
          const index = parseInt(e.target.getAttribute("data-index"))
          const field = e.target.className.split("-")[1] // description, amount, type, category
          
          if (field === "description") {
            window.currentEditingTransactions[index].name = e.target.value
          } else if (field === "amount") {
            window.currentEditingTransactions[index].amount = parseFloat(e.target.value) || 0
          } else if (field === "type") {
            window.currentEditingTransactions[index].type = e.target.value
          } else if (field === "category") {
            window.currentEditingTransactions[index].category = e.target.value
          }
        })
      })

      // Save button event
      const saveBtn = document.getElementById("saveAllTransactionsBtn")
      if (saveBtn) {
        saveBtn.onclick = () => {
          if (!isProcessing && window.currentEditingTransactions.length > 0) {
            saveTransactions(window.currentEditingTransactions)
            modal.style.display = "none"
            document.body.style.overflow = "auto"
          }
        }
      }

      // Hiển thị modal
      modal.style.display = "block"
      document.body.style.overflow = "hidden"
      console.log("Multiple transactions modal opened successfully")
    }

        // Hiển thị giao dịch đơn giản và đẹp
    const displayTransactions = (transactions) => {
      if (!transactions || transactions.length === 0) return

      // Container chính
      const container = document.createElement("div")
      container.className = "message bot"
      container.style.cssText = `
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 12px;
      `

      // Avatar bot
      const avatar = document.createElement("div")
      avatar.style.cssText = `
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--accent-color);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: white;
        font-size: 0.8rem;
      `
      avatar.innerHTML = '<i class="fas fa-robot"></i>'

      // Content card
      const card = document.createElement("div")
      card.style.cssText = `
        background: var(--card-background);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        border-top-left-radius: 3px;
        padding: 0;
        max-width: 320px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
      `

      // Header đơn giản
      const header = document.createElement("div")
      header.style.cssText = `
        background: var(--hover-color);
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
      `

      const title = document.createElement("div")
      title.style.cssText = `
        color: var(--primary-color);
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-right: 20px;
      `
      title.innerHTML = '<i class="fas fa-receipt" style="color: var(--accent-color); font-size: 0.8rem;"></i>Giao dịch phát hiện'

      const buttonGroup = document.createElement("div")
      buttonGroup.style.cssText = `
        display: flex;
        gap: 6px;
      `

      // Nút Sửa
      const editButton = document.createElement("button")
      editButton.textContent = "Sửa"
      editButton.style.cssText = `
        background: var(--accent-color);
        color: white;
        border: none;
        border-radius: 4px;
        padding: 4px 10px;
        font-size: 0.75rem;
        cursor: pointer;
        transition: background 0.2s ease;
        font-weight: 500;
      `
      editButton.addEventListener("mouseover", () => editButton.style.background = "#2563eb")
      editButton.addEventListener("mouseout", () => editButton.style.background = "var(--accent-color)")
      editButton.addEventListener("click", () => {
        if (!isProcessing) showEditTransactionsModal(transactions)
      })

      // Nút Lưu
      const saveButton = document.createElement("button")
      saveButton.textContent = "Lưu"
      saveButton.style.cssText = `
        background: #22c55e;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 4px 10px;
        font-size: 0.75rem;
        cursor: pointer;
        transition: background 0.2s ease;
        font-weight: 500;
      `
      saveButton.addEventListener("mouseover", () => saveButton.style.background = "#16a34a")
      saveButton.addEventListener("mouseout", () => saveButton.style.background = "#22c55e")
      saveButton.addEventListener("click", () => {
        if (!isProcessing) saveTransactions(transactions)
      })

      buttonGroup.appendChild(editButton)
      buttonGroup.appendChild(saveButton)
      header.appendChild(title)
      header.appendChild(buttonGroup)

      // Danh sách giao dịch
      const transactionList = document.createElement("div")
      transactionList.style.cssText = `
        padding: 12px 16px;
        display: flex;
        flex-direction: column;
        gap: 6px;
      `

      transactions.forEach((transaction) => {
        const row = document.createElement("div")
        row.style.cssText = `
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 10px 12px;
          background: var(--card-background);
          border: 1px solid var(--border-color);
          border-radius: 6px;
          min-height: 40px;
          transition: all 0.2s ease;
        `

        // Hover effect
        row.addEventListener("mouseover", () => {
          row.style.background = "var(--hover-color)"
          row.style.borderColor = "var(--accent-color)"
        })
        row.addEventListener("mouseout", () => {
          row.style.background = "var(--card-background)"
          row.style.borderColor = "var(--border-color)"
        })

        // Phần bên trái (icon + tên)
        const leftSide = document.createElement("div")
        leftSide.style.cssText = `
          display: flex;
          align-items: center;
          gap: 10px;
          flex: 1;
          min-width: 0;
        `

        const isIncome = transaction.type === "income"
        const typeIcon = document.createElement("div")
        typeIcon.style.cssText = `
          width: 24px;
          height: 24px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          flex-shrink: 0;
          background: ${isIncome ? "#dcfce7" : "#fee2e2"};
          color: ${isIncome ? "#16a34a" : "#dc2626"};
          font-size: 0.7rem;
        `
        typeIcon.innerHTML = isIncome ? '<i class="fas fa-plus"></i>' : '<i class="fas fa-minus"></i>'

        const transactionInfo = document.createElement("div")
        transactionInfo.style.cssText = `
          flex: 1;
          min-width: 0;
        `

        const transactionName = document.createElement("div")
        transactionName.textContent = transaction.name
        transactionName.style.cssText = `
          color: var(--primary-color);
          font-size: 0.85rem;
          font-weight: 500;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        `

        const transactionCategory = document.createElement("div")
        transactionCategory.textContent = transaction.category || "Chung"
        transactionCategory.style.cssText = `
          color: var(--secondary-color);
          font-size: 0.7rem;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          margin-top: 2px;
        `

        transactionInfo.appendChild(transactionName)
        transactionInfo.appendChild(transactionCategory)

        leftSide.appendChild(typeIcon)
        leftSide.appendChild(transactionInfo)

        // Phần bên phải (số tiền)
        const amountSpan = document.createElement("span")
        amountSpan.textContent = new Intl.NumberFormat("vi-VN").format(transaction.amount) + "₫"
        amountSpan.style.cssText = `
          color: ${isIncome ? "#16a34a" : "#dc2626"};
          font-weight: 600;
          font-size: 0.85rem;
          flex-shrink: 0;
          margin-left: 12px;
        `

        row.appendChild(leftSide)
        row.appendChild(amountSpan)
        transactionList.appendChild(row)
      })

      card.appendChild(header)
      card.appendChild(transactionList)
      container.appendChild(avatar)
      container.appendChild(card)
      qaMessages.appendChild(container)
      qaMessages.scrollTop = qaMessages.scrollHeight
    }

    // Hàm phân tích ảnh hóa đơn
    const analyzeBillImage = async (file) => {
      if (!file || isProcessing) return

      isProcessing = true

      try {
        console.log("Bắt đầu phân tích ảnh...")
        
        // Hiển thị thông báo đang xử lý
        showTypingIndicator()

        // Tạo FormData để gửi file
        const formData = new FormData()
        formData.append("file", file)
        formData.append("input_text", "phân tích ảnh")

        console.log("Gửi ảnh đến API phân tích...")

        // Gửi ảnh đến API phân tích
        const response = await fetch("http://127.0.0.1:8506/analyze-bill", {
          method: "POST",
          headers: {
            accept: "application/json",
          },
          body: formData,
        })

        console.log("Nhận phản hồi từ analyze-bill API:", response.status)

        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`)
        }

        const data = await response.json()
        console.log("Dữ liệu phân tích:", data)
        
        // Không xóa typing indicator ở đây, để nó tiếp tục hiển thị
        // cho đến khi chat API trả về kết quả

        // Hiển thị kết quả phân tích
        if (data.output_text) {
          console.log("Gửi kết quả đến chat API...")
          console.log("Output text:", data.output_text)
          
          try {
            // Test với hàm đơn giản hơn
            const chatResult = await testChatAPI(data.output_text)
            console.log("Kết quả từ chat API:", chatResult)
            
            // Xóa typing indicator và hiển thị kết quả
            removeTypingIndicator()
            
            if (chatResult.message) {
              addMessageToChat(chatResult.message, "bot")
              
              // Nếu phát hiện giao dịch, hiển thị sau một khoảng thời gian ngắn
              if (chatResult.bill && chatResult.bill.length > 0) {
                console.log("Phát hiện giao dịch:", chatResult.bill)
                setTimeout(() => {
                  displayTransactions(chatResult.bill)
                }, 500)
              }
            } else {
              addMessageToChat("Xin lỗi, tôi không thể xử lý kết quả phân tích.", "bot")
            }
            
          } catch (chatError) {
            console.error("Lỗi khi gọi chat API:", chatError)
            removeTypingIndicator()
            addMessageToChat("Đã phân tích ảnh thành công nhưng không thể xử lý kết quả. Vui lòng thử lại.", "bot")
          }
        } else {
          console.log("Không có output_text từ API")
          removeTypingIndicator()
          addMessageToChat("Không thể phân tích ảnh. Vui lòng thử lại với ảnh khác.", "bot")
        }
      } catch (error) {
        console.error("Lỗi khi phân tích ảnh:", error)
        removeTypingIndicator()
        addMessageToChat("Đã xảy ra lỗi khi phân tích ảnh. Vui lòng thử lại sau.", "bot")
      } finally {
        isProcessing = false
      }
    }

    // Send message functionality with external API
    const sendChatMessage = async (externalMessage = null, showUserMessage = true) => {
      const message = externalMessage || questionInput.value.trim()
      
      console.log("sendChatMessage - Kiểm tra điều kiện:", { 
        message: message, 
        messageLength: message.length,
        isProcessing: isProcessing,
        showUserMessage: showUserMessage 
      })
      
      if (!message) {
        console.log("Không có message, thoát")
        return
      }
      
      if (isProcessing) {
        console.log("Đang xử lý, thoát")
        return
      }

      console.log("sendChatMessage được gọi:", { message, showUserMessage })

      isProcessing = true

      try {
        // Thêm tin nhắn người dùng vào chat nếu cần
        if (showUserMessage) {
          addMessageToChat(message, "user")
          questionInput.value = ""
        }

        // Hiển thị chỉ báo đang nhập nếu không phải từ phân tích ảnh
        if (showUserMessage) {
          showTypingIndicator()
        }

        // Lấy role được chọn
        const selectedRole = roleSelect ? roleSelect.value : "Trợ lý thông minh"

        // Chuẩn bị dữ liệu gửi đi
        const requestData = {
          id_user: document.getElementById("user-id")?.value || "user",
          message: message,
          role: selectedRole,
        }

        console.log("Gửi dữ liệu đến chat API:", requestData)

        // Gửi yêu cầu đến API bên ngoài
        const response = await fetch("http://127.0.0.1:8506/chat", {
          method: "POST",
          headers: {
            accept: "application/json",
            "Content-Type": "application/json",
          },
          body: JSON.stringify(requestData),
        })

        console.log("Nhận phản hồi từ chat API:", response.status)

        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`)
        }

        const data = await response.json()
        console.log("Dữ liệu từ chat API:", data)

        // Xóa chỉ báo đang nhập khi có phản hồi
        removeTypingIndicator()

        // Thêm phản hồi bot
        if (data.message) {
          addMessageToChat(data.message, "bot")

          // Nếu phát hiện giao dịch, hiển thị sau một khoảng thời gian ngắn
          if (data.bill && data.bill.length > 0) {
            console.log("Phát hiện giao dịch:", data.bill)
            setTimeout(() => {
              displayTransactions(data.bill)
            }, 500)
          }
        } else {
          console.log("Không có message trong phản hồi")
          addMessageToChat("Xin lỗi, tôi không thể xử lý tin nhắn của bạn lúc này.", "bot")
        }
      } catch (error) {
        console.error("Lỗi khi gọi chat API:", error)
        removeTypingIndicator()
        addMessageToChat("Đã xảy ra lỗi kết nối. Vui lòng thử lại sau.", "bot")
      } finally {
        isProcessing = false
        console.log("sendChatMessage hoàn thành")
      }
    }

    // Setup image upload functionality
    const setupImageUpload = () => {
      // Tìm phần qa-input
      const qaInput = document.querySelector(".qa-input")
      if (!qaInput) {
        console.log("qa-input not found")
        return
      }

      // Kiểm tra đã có nút upload chưa để tránh duplicate
      if (qaInput.querySelector("#bill-image-upload")) {
        console.log("Upload button already exists")
        return
      }

      // Tạo nút tải lên ảnh với CSS variables
      const uploadButton = document.createElement("button")
      uploadButton.className = "btn-icon upload-image-btn"
      uploadButton.title = "Tải lên ảnh hóa đơn"
      uploadButton.innerHTML = '<i class="fas fa-image"></i>'
      uploadButton.style.cssText = `
        margin-right: 10px;
        background: none;
        border: none;
        color: var(--accent-color);
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
        transition: all 0.2s ease;
        font-size: 1rem;
      `

      // Tạo input file ẩn
      const fileInput = document.createElement("input")
      fileInput.type = "file"
      fileInput.accept = "image/*"
      fileInput.style.display = "none"
      fileInput.id = "bill-image-upload"

      // Thêm sự kiện cho nút tải lên
      uploadButton.addEventListener("click", () => {
        if (!isProcessing) {
          fileInput.click()
        }
      })

      // Hover effects sử dụng CSS variables
      uploadButton.addEventListener("mouseover", () => {
        uploadButton.style.background = "var(--hover-color)"
        uploadButton.style.color = "var(--primary-color)"
      })
      uploadButton.addEventListener("mouseout", () => {
        uploadButton.style.background = "none"
        uploadButton.style.color = "var(--accent-color)"
      })

      // Xử lý khi người dùng chọn file
      fileInput.addEventListener("change", (e) => {
        if (e.target.files && e.target.files[0]) {
          const file = e.target.files[0]

          // Kiểm tra loại file
          if (!file.type.startsWith("image/")) {
            addMessageToChat("Vui lòng chọn file ảnh hợp lệ.", "bot")
            return
          }

          // Hiển thị ảnh đã chọn với CSS variables
          const reader = new FileReader()
          reader.onload = (event) => {
            // Tạo tin nhắn với ảnh
            const messageElement = document.createElement("div")
            messageElement.className = "message user"
            messageElement.style.cssText = `
              display: flex;
              align-items: flex-start;
              gap: 10px;
              margin-bottom: 12px;
              flex-direction: row-reverse;
            `

            const avatar = document.createElement("div")
            avatar.className = "message-avatar"
            avatar.style.cssText = `
              width: 32px;
              height: 32px;
              border-radius: 50%;
              background: var(--primary-color);
              display: flex;
              align-items: center;
              justify-content: center;
              flex-shrink: 0;
              color: white;
              font-size: 0.8rem;
            `
            avatar.innerHTML = '<i class="fas fa-user"></i>'

            const messageContent = document.createElement("div")
            messageContent.className = "message-content"
            messageContent.style.cssText = `
              padding: 12px 16px;
              border-radius: 8px;
              border-top-right-radius: 3px;
              max-width: 80%;
              background: var(--accent-color);
              color: white;
              border: 1px solid var(--accent-color);
            `

            const imagePreview = document.createElement("img")
            imagePreview.src = event.target.result
            imagePreview.style.cssText = `
              max-width: 100%;
              max-height: 200px;
              border-radius: 8px;
              display: block;
            `

            messageContent.appendChild(imagePreview)
            messageElement.appendChild(avatar)
            messageElement.appendChild(messageContent)
            qaMessages.appendChild(messageElement)
            qaMessages.scrollTop = qaMessages.scrollHeight

            // Phân tích ảnh
            analyzeBillImage(file)
          }

          reader.readAsDataURL(file)

          // Reset input để có thể chọn lại cùng một file
          fileInput.value = ""
        }
      })

      // Chèn các phần tử vào DOM một cách an toàn
      try {
        const inputContainer = qaInput.querySelector('div[style*="flex:1"]') || qaInput.querySelector('.qa-input > div:first-child')
        if (inputContainer) {
          qaInput.insertBefore(uploadButton, inputContainer)
        } else {
          qaInput.prepend(uploadButton)
        }
        qaInput.appendChild(fileInput)
        console.log("✅ Image upload setup completed")
      } catch (error) {
        console.error("❌ Error setting up image upload:", error)
      }
    }

    // Event listeners
    sendQuestion.addEventListener("click", () => sendChatMessage())
    questionInput.addEventListener("keypress", function(e) {
      if (e.key === "Enter" && !isProcessing) {
        e.preventDefault()
        sendChatMessage()
      }
    })

    // Input animation
    questionInput.addEventListener("input", function() {
      if (this.value.trim()) {
        sendQuestion.classList.add("send-button-active")
      } else {
        sendQuestion.classList.remove("send-button-active")
      }
    })

    // Initialize image upload
    setupImageUpload()
  }

  // Test function để gọi chat API trực tiếp
  const testChatAPI = async (message) => {
    console.log("Test chat API với message:", message)
    
    try {
      const requestData = {
        id_user: document.getElementById("user-id")?.value || "user",
        message: message,
        role: "Trợ lý thông minh",
      }

      console.log("Test - Gửi dữ liệu:", requestData)

      const response = await fetch("http://127.0.0.1:8506/chat", {
        method: "POST",
        headers: {
          accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify(requestData),
      })

      console.log("Test - Nhận phản hồi:", response.status)

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }

      const data = await response.json()
      console.log("Test - Dữ liệu:", data)
      
      return data
    } catch (error) {
      console.error("Test - Lỗi:", error)
      throw error
    }
  }

  // Simple edit transaction handler
  window.handleEditTransaction = function(button) {
    console.log("Edit clicked", button)
    
    // Get data
    const id = button.getAttribute("data-id")
    const amount = button.getAttribute("data-amount")
    const description = button.getAttribute("data-description")
    const type = button.getAttribute("data-type")
    const category = button.getAttribute("data-category")
    const date = button.getAttribute("data-date")

    // Fill form
    document.getElementById("edit-transaction-id").value = id || ""
    document.getElementById("edit-amount").value = amount || ""
    document.getElementById("edit-description").value = description || ""
    document.getElementById("edit-date").value = date || ""
    document.getElementById("edit-type").value = type || "expense"
    
    // Show modal
    const modal = document.getElementById("editTransactionModal")
    modal.style.display = "block"
    document.body.style.overflow = "hidden"
  }

  // Make updateCategoryVisibility global too
  window.updateCategoryVisibility = function(typeSelect, incomeId, expenseId, categoryId) {
    const incomeCategories = document.getElementById(incomeId)
    const expenseCategories = document.getElementById(expenseId)
    const categorySelect = document.getElementById(categoryId)

    if (!typeSelect || !incomeCategories || !expenseCategories || !categorySelect) return

    const isIncome = typeSelect.value === "income"
    incomeCategories.style.display = isIncome ? "" : "none"
    expenseCategories.style.display = isIncome ? "none" : ""

    // Select first option of visible category group
    const visibleGroup = isIncome ? incomeCategories : expenseCategories
    const firstOption = visibleGroup.querySelector("option")
    if (firstOption) categorySelect.value = firstOption.value
  }

  // Initialize all components
  try {
    console.log("Initializing components...")
    setupTransactionModals()
    setupCategoryModals()
    setupChatbot()
    console.log("Components initialized successfully")
    
    // Test function để kiểm tra edit button
    window.testEditModal = function() {
      console.log("Testing edit modal...")
      openModal("editTransactionModal")
    }
  } catch (error) {
    console.error("Error initializing components:", error)
  }
})

