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
      const tabPane = document.getElementById(tabId)
      if (tabPane) tabPane.classList.add("active")
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
          if (!categoryCard) return

          const categoryData = categoryCard.querySelector(".category-data")
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

  // Chatbot functionality - COMPLETELY REWRITTEN VERSION
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

    // Xóa các tin nhắn mẫu
    document.querySelectorAll(".question-item").forEach((item) => {
      item.remove()
    })

    // Biến để theo dõi trạng thái xử lý
    let isProcessing = false
    // Biến lưu trữ giao dịch hiện tại
    let currentTransactions = []

    // Thêm tin nhắn vào chat - Đơn giản hóa
    const addMessageToChat = (message, sender) => {
      const messageElement = document.createElement("div")
      messageElement.className = `message ${sender}`

      const messageContent = document.createElement("div")
      messageContent.className = "message-content"

      // Thay thế các ký tự xuống dòng bằng phần tử <br>
      const formattedMessage = message.split("\n").map((line) => {
        const span = document.createElement("span")
        span.textContent = line
        return span
      })

      messageContent.innerHTML = ""
      formattedMessage.forEach((span, index) => {
        messageContent.appendChild(span)
        if (index < formattedMessage.length - 1) {
          messageContent.appendChild(document.createElement("br"))
        }
      })

      messageElement.appendChild(messageContent)
      qaMessages.appendChild(messageElement)
      qaMessages.scrollTop = qaMessages.scrollHeight

      return messageElement
    }

    // Hiển thị chỉ báo đang nhập
    const showTypingIndicator = () => {
      const typingElement = document.createElement("div")
      typingElement.className = "message bot typing-indicator"
      typingElement.id = "typing-indicator"

      const typingContent = document.createElement("div")
      typingContent.className = "message-content"

      for (let i = 0; i < 3; i++) {
        const dot = document.createElement("span")
        typingContent.appendChild(dot)
      }

      typingElement.appendChild(typingContent)
      qaMessages.appendChild(typingElement)
      qaMessages.scrollTop = qaMessages.scrollHeight
    }

    // Xóa chỉ báo đang nhập
    const removeTypingIndicator = () => {
      const typingIndicator = document.getElementById("typing-indicator")
      if (typingIndicator) typingIndicator.remove()
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

    // Lưu giao dịch vào server - Đơn giản hóa
    const saveTransactions = async (transactions) => {
      if (!transactions || transactions.length === 0 || isProcessing) return

      isProcessing = true

      try {
        // Hiển thị tin nhắn đang lưu
        const savingMessage = addMessageToChat("Đang lưu giao dịch...", "bot")

        // Giới hạn số lượng giao dịch để tránh quá tải
        const transactionsToSave = transactions.slice(0, 5)

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
                // Thêm thông báo xóa lịch sử thành công nếu cần
                addMessageToChat("Lịch sử chat đã được xóa", "bot")
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

    // Hiển thị popup chỉnh sửa giao dịch
    const showEditTransactionsModal = (transactions) => {
      if (!transactions || transactions.length === 0) return

      // Lưu giao dịch hiện tại
      currentTransactions = [...transactions]

      // Lấy modal
      const modal = document.getElementById("editTransactionsModal")
      if (!modal) return

      // Xóa nội dung cũ
      const transactionData = document.getElementById("transaction-data")
      if (transactionData) {
        transactionData.innerHTML = ""
      }

      // Thêm giao dịch vào modal
      transactions.forEach((transaction, index) => {
        const item = document.createElement("div")
        item.className = "transaction-item"
        item.dataset.index = index

        // Loại giao dịch
        const typeSelect = document.createElement("select")
        typeSelect.className = "transaction-type-select"
        typeSelect.innerHTML = `
          <option value="income" ${transaction.type === "income" ? "selected" : ""}>Thu nhập</option>
          <option value="expense" ${transaction.type !== "income" ? "selected" : ""}>Chi tiêu</option>
        `
        typeSelect.addEventListener("change", (e) => {
          currentTransactions[index].type = e.target.value
        })

        // Mô tả
        const descInput = document.createElement("input")
        descInput.type = "text"
        descInput.className = "transaction-description-input"
        descInput.value = transaction.name
        descInput.addEventListener("input", (e) => {
          currentTransactions[index].name = e.target.value
        })

        // Số tiền
        const amountInput = document.createElement("input")
        amountInput.type = "number"
        amountInput.className = "transaction-amount-input"
        amountInput.value = transaction.amount
        amountInput.addEventListener("input", (e) => {
          currentTransactions[index].amount = Number.parseFloat(e.target.value) || 0
        })

        // Nút xóa
        const deleteBtn = document.createElement("button")
        deleteBtn.className = "btn-icon delete"
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>'
        deleteBtn.addEventListener("click", () => {
          currentTransactions.splice(index, 1)
          item.remove()

          // Nếu không còn giao dịch nào, đóng modal
          if (currentTransactions.length === 0) {
            modal.style.display = "none"
            document.body.style.overflow = "auto"
          }
        })

        // Thêm các phần tử vào item
        item.appendChild(typeSelect)
        item.appendChild(descInput)
        item.appendChild(amountInput)
        item.appendChild(deleteBtn)

        // Thêm item vào modal
        if (transactionData) {
          transactionData.appendChild(item)
        }
      })

      // Cập nhật nút lưu
      const saveBtn = document.getElementById("saveTransactionsBtn")
      if (saveBtn) {
        saveBtn.onclick = () => {
          if (!isProcessing) {
            saveTransactions(currentTransactions)
          }
        }
      }

      // Hiển thị modal
      modal.style.display = "block"
      document.body.style.overflow = "hidden"
    }

    // Hiển thị giao dịch đơn giản
    const displayTransactions = (transactions) => {
      if (!transactions || transactions.length === 0) return

      // Tạo thông báo đơn giản
      let message = "Tôi đã phát hiện các giao dịch sau:\n\n"

      transactions.forEach((transaction, index) => {
        const type = transaction.type === "income" ? "Thu nhập" : "Chi tiêu"
        const amount = new Intl.NumberFormat("vi-VN").format(transaction.amount)
        message += `${index + 1}. ${type}: ${transaction.name} - ${amount}đ\n`
      })

      message += "\nBạn có muốn chỉnh sửa và lưu các giao dịch này không?"

      // Hiển thị thông báo
      addMessageToChat(message, "bot")

      // Tạo các nút hành động
      const buttonContainer = document.createElement("div")
      buttonContainer.className = "message bot"
      buttonContainer.style.display = "flex"
      buttonContainer.style.gap = "10px"

      // Nút chỉnh sửa
      const editButton = document.createElement("button")
      editButton.className = "edit-transactions-btn"
      editButton.textContent = "Chỉnh sửa giao dịch"
      editButton.style.backgroundColor = "#3b82f6"
      editButton.style.color = "white"
      editButton.style.border = "none"
      editButton.style.borderRadius = "4px"
      editButton.style.padding = "8px 16px"
      editButton.style.cursor = "pointer"
      editButton.addEventListener("click", () => {
        if (!isProcessing) {
          showEditTransactionsModal(transactions)
        }
      })

      // Nút lưu ngay
      const saveButton = document.createElement("button")
      saveButton.className = "save-transactions-btn"
      saveButton.textContent = "Lưu ngay"
      saveButton.style.backgroundColor = "#22c55e"
      saveButton.style.color = "white"
      saveButton.style.border = "none"
      saveButton.style.borderRadius = "4px"
      saveButton.style.padding = "8px 16px"
      saveButton.style.cursor = "pointer"
      saveButton.addEventListener("click", () => {
        if (!isProcessing) {
          saveTransactions(transactions)
        }
      })

      buttonContainer.appendChild(editButton)
      buttonContainer.appendChild(saveButton)
      qaMessages.appendChild(buttonContainer)
      qaMessages.scrollTop = qaMessages.scrollHeight
    }

    // Gửi tin nhắn đến API chatbot - Đơn giản hóa
    const sendChatMessage = async () => {
      const message = questionInput.value.trim()
      if (!message || isProcessing) return

      isProcessing = true

      try {
        // Thêm tin nhắn người dùng vào chat
        addMessageToChat(message, "user")
        questionInput.value = ""

        // Hiển thị chỉ báo đang nhập
        showTypingIndicator()

        // Lấy role được chọn
        const selectedRole = roleSelect ? roleSelect.value : "Trợ lý thông minh"

        // Chuẩn bị dữ liệu gửi đi
        const requestData = {
          id_user: document.getElementById("user-id")?.value || "user",
          message: message,
          role: selectedRole,
        }

        // Gửi yêu cầu đến API bên ngoài
        const response = await fetch("http://127.0.0.1:8506/chat", {
          method: "POST",
          headers: {
            accept: "application/json",
            "Content-Type": "application/json",
          },
          body: JSON.stringify(requestData),
        })

        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`)
        }

        const data = await response.json()

        // Xóa chỉ báo đang nhập
        removeTypingIndicator()

        // Thêm phản hồi bot
        if (data.message) {
          addMessageToChat(data.message, "bot")

          // Nếu phát hiện giao dịch, hiển thị sau một khoảng thời gian ngắn
          if (data.bill && data.bill.length > 0) {
            setTimeout(() => {
              displayTransactions(data.bill)
            }, 500)
          }
        } else {
          addMessageToChat("Xin lỗi, tôi không thể xử lý tin nhắn của bạn lúc này.", "bot")
        }
      } catch (error) {
        console.error("Error:", error)
        removeTypingIndicator()
        addMessageToChat("Đã xảy ra lỗi kết nối. Vui lòng thử lại sau.", "bot")
      } finally {
        isProcessing = false
      }
    }

    // Sự kiện chatbot
    if (sendQuestion) {
      sendQuestion.addEventListener("click", () => {
        if (!isProcessing) {
          sendChatMessage()
        }
      })
    }

    if (questionInput) {
      questionInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter" && !isProcessing) {
          e.preventDefault() // Ngăn form submit
          sendChatMessage()
        }
      })
    }
  }

  // Initialize all components
  try {
    setupTransactionModals()
    setupCategoryModals()
    setupChatbot()
  } catch (error) {
    console.error("Error initializing components:", error)
  }
})
