document.addEventListener("DOMContentLoaded", () => {
  // Theme management
  const body = document.body
  const savedTheme = localStorage.getItem("theme")
  if (savedTheme === "dark") body.classList.add("dark-mode")

  // Theme toggle button
  const themeToggle = document.getElementById("themeToggle")
  if (themeToggle) {
    themeToggle.innerHTML = body.classList.contains("dark-mode")
      ? '<i class="fas fa-sun"></i>'
      : '<i class="fas fa-moon"></i>'

    themeToggle.addEventListener("click", () => {
      body.classList.toggle("dark-mode")
      const isDark = body.classList.contains("dark-mode")
      localStorage.setItem("theme", isDark ? "dark" : "light")
      themeToggle.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>'
    })
  }

  // Dropdown menu
  document.querySelectorAll(".dropdown-toggle").forEach((toggle) => {
    toggle.addEventListener("click", (e) => {
      e.preventDefault()
      const dropdown = toggle.nextElementSibling
      dropdown.style.display = dropdown.style.display === "block" ? "none" : "block"
    })
  })

  // Close dropdowns when clicking outside
  document.addEventListener("click", (e) => {
    if (!e.target.matches(".dropdown-toggle")) {
      document.querySelectorAll(".dropdown-menu").forEach((dropdown) => {
        if (dropdown.style.display === "block") dropdown.style.display = "none"
      })
    }
  })

  // Tab navigation
  document.querySelectorAll(".tab-button").forEach((button) => {
    button.addEventListener("click", () => {
      const tabId = button.dataset.tab
      document.querySelectorAll(".tab-button").forEach((btn) => btn.classList.remove("active"))
      document.querySelectorAll(".tab-pane").forEach((pane) => pane.classList.remove("active"))
      button.classList.add("active")
      document.getElementById(tabId)?.classList.add("active")
    })
  })

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
    const modal = document.getElementById(modalId)
    if (modal) {
      modal.style.display = "block"
      document.body.style.overflow = "hidden"
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
    if (editTransactionBtns.length > 0) {
      editTransactionBtns.forEach((btn) => {
        btn.addEventListener("click", (e) => {
          e.stopPropagation()

          // Get transaction data from button attributes
          const id = btn.getAttribute("data-id")
          const amount = btn.getAttribute("data-amount")
          const description = btn.getAttribute("data-description")
          const type = btn.getAttribute("data-type")
          const category = btn.getAttribute("data-category")
          const date = btn.getAttribute("data-date")

          // Fill the edit form
          document.getElementById("edit-transaction-id").value = id
          document.getElementById("edit-amount").value = amount
          document.getElementById("edit-description").value = description
          document.getElementById("edit-date").value = date

          // Set transaction type and update category options
          const typeSelect = document.getElementById("edit-type")
          typeSelect.value = type

          // Show/hide appropriate category options
          updateCategoryVisibility(typeSelect, "edit-income-categories", "edit-expense-categories", "edit-category")

          // Set category
          document.getElementById("edit-category").value = category

          // Open modal
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
    if (editCategoryBtns.length > 0) {
      editCategoryBtns.forEach((btn) => {
        btn.addEventListener("click", (e) => {
          e.stopPropagation() // Prevent card click event
          const categoryId = btn.getAttribute("data-id")
          const categoryCard = document.querySelector(`.category-card[data-id="${categoryId}"]`)
          const categoryData = categoryCard.querySelector(".category-data")

          // Fill the edit form with category data
          document.getElementById("edit-category-id").value = categoryData.getAttribute("data-id")
          document.getElementById("edit-name").value = categoryData.getAttribute("data-name")
          document.getElementById("edit-description").value = categoryData.getAttribute("data-description")
          document.getElementById("edit-type").value = categoryData.getAttribute("data-type")

          // Update usage info
          const usageCount = Number.parseInt(categoryData.getAttribute("data-usage"))
          const usageText = document.getElementById("edit-usage-text")
          const usageHint = document.getElementById("edit-usage-hint")

          if (usageCount > 0) {
            usageText.textContent = `Thể loại này đang được sử dụng trong ${usageCount} giao dịch.`
            usageHint.style.display = "block"
          } else {
            usageText.textContent = "Thể loại này chưa được sử dụng trong bất kỳ giao dịch nào."
            usageHint.style.display = "none"
          }

          openModal("editCategoryModal")
        })
      })
    }

    // Make category cards clickable to edit
    const categoryCards = document.querySelectorAll(".category-card")
    if (categoryCards.length > 0) {
      categoryCards.forEach((card) => {
        card.addEventListener("click", () => {
          const editBtn = card.querySelector(".edit-category-btn")
          if (editBtn) {
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

  // Chatbot functionality
  const setupChatbot = () => {
    const qaMessages = document.getElementById("qaMessages")
    const questionInput = document.getElementById("questionInput")
    const sendQuestion = document.getElementById("sendQuestion")
    const questionItems = document.querySelectorAll(".question-item")

    if (!qaMessages || !questionInput || !sendQuestion) return

    // Add message to chat
    const addMessageToChat = (message, sender) => {
      const messageElement = document.createElement("div")
      messageElement.classList.add("message", sender)

      const messageContent = document.createElement("div")
      messageContent.classList.add("message-content")
      messageContent.innerHTML = message.replace(/\n/g, "<br>")

      messageElement.appendChild(messageContent)
      qaMessages.appendChild(messageElement)
      qaMessages.scrollTop = qaMessages.scrollHeight
    }

    // Show typing indicator
    const showTypingIndicator = () => {
      const typingElement = document.createElement("div")
      typingElement.classList.add("message", "bot", "typing-indicator")

      const typingContent = document.createElement("div")
      typingContent.classList.add("message-content")
      typingContent.innerHTML = "<span>.</span><span>.</span><span>.</span>"

      typingElement.appendChild(typingContent)
      qaMessages.appendChild(typingElement)
      qaMessages.scrollTop = qaMessages.scrollHeight
    }

    // Remove typing indicator
    const removeTypingIndicator = () => {
      const typingIndicator = document.querySelector(".typing-indicator")
      if (typingIndicator) typingIndicator.remove()
    }

    // Send message to chatbot
    const sendChatMessage = () => {
      const message = questionInput.value.trim()
      if (!message) return

      // Add user message to chat
      addMessageToChat(message, "user")
      questionInput.value = ""
      showTypingIndicator()

      // Send to server
      fetch("api/chatbot-api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message }),
      })
        .then((response) => response.json())
        .then((data) => {
          removeTypingIndicator()
          if (data.response) {
            addMessageToChat(data.response, "bot")
          } else if (data.error) {
            addMessageToChat("Đã xảy ra lỗi. Vui lòng thử lại sau.", "bot")
          }
        })
        .catch((error) => {
          removeTypingIndicator()
          addMessageToChat("Đã xảy ra lỗi kết nối. Vui lòng thử lại sau.", "bot")
          console.error("Error:", error)
        })
    }

    // Chatbot event listeners
    sendQuestion.addEventListener("click", sendChatMessage)

    questionInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") sendChatMessage()
    })

    if (questionItems.length > 0) {
      questionItems.forEach((item) => {
        item.addEventListener("click", () => {
          questionInput.value = item.textContent
          questionInput.focus()
        })
      })
    }
  }

  // Initialize all components
  setupTransactionModals()
  setupCategoryModals()
  setupChatbot()
})

