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
  
  // Listen for storage changes (đồng bộ giữa các tab/trang)
  window.addEventListener('storage', function(e) {
    if (e.key === 'darkMode') {
      const newDarkMode = e.newValue === 'true'
      if (newDarkMode !== isDarkMode) {
        isDarkMode = newDarkMode
        if (isDarkMode) {
          body.classList.add('dark-mode')
        } else {
          body.classList.remove('dark-mode')
        }
        updateButton(isDarkMode)
        console.log(`🔄 Dark mode synced: ${isDarkMode ? 'dark' : 'light'}`)
      }
    }
  })
  
  // Add click event
  if (darkModeBtn) {
    darkModeBtn.addEventListener('click', toggleDarkMode)
    console.log("✅ Dark mode toggle ready!")
  } else {
    console.log("ℹ️ Dark mode button not found on this page")
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

// Text-to-Speech function
const speakText = async (text, voiceType, buttonElement) => {
  if (!('speechSynthesis' in window)) {
    console.log('Speech synthesis not supported')
    return
  }

  // Stop any ongoing speech
  speechSynthesis.cancel()

  const utterance = new SpeechSynthesisUtterance(text)
  
  // Configure voice based on role
  const voices = speechSynthesis.getVoices()
  let selectedVoice = null
  
  switch(voiceType) {
    case 'Mama nóng tính':
      selectedVoice = voices.find(voice => voice.name.includes('Female') || voice.name.includes('Woman'))
      utterance.rate = 1.2
      utterance.pitch = 1.1
      break
    case 'Homie':
      selectedVoice = voices.find(voice => voice.name.includes('Male') || voice.name.includes('Man'))
      utterance.rate = 0.9
      utterance.pitch = 0.9
      break
    default:
      selectedVoice = voices.find(voice => voice.lang.includes('vi') || voice.lang.includes('en'))
      utterance.rate = 1.0
      utterance.pitch = 1.0
  }
  
  if (selectedVoice) {
    utterance.voice = selectedVoice
  }
  
  utterance.lang = 'vi-VN'
  utterance.volume = 0.8
  
  // Update button during speech
  if (buttonElement) {
    const originalHTML = buttonElement.innerHTML
    buttonElement.innerHTML = '<i class="fas fa-stop"></i>'
    buttonElement.style.background = 'var(--negative-color)'
    buttonElement.style.borderColor = 'var(--negative-color)'
    
    utterance.onend = () => {
      buttonElement.innerHTML = originalHTML
      buttonElement.style.background = 'var(--hover-color)'
      buttonElement.style.borderColor = 'var(--border-color)'
    }
    
    utterance.onerror = () => {
      buttonElement.innerHTML = originalHTML
      buttonElement.style.background = 'var(--hover-color)'
      buttonElement.style.borderColor = 'var(--border-color)'
    }
  }
  
  speechSynthesis.speak(utterance)
}

const setupChatbot = () => {
  console.log("Setting up chatbot...")
  
  const qaMessages = document.getElementById("qaMessages")
  const questionInput = document.getElementById("questionInput")
  const sendQuestion = document.getElementById("sendQuestion")
  const roleSelect = document.getElementById("chatRoleSelect")
  const transactionsContainer = document.getElementById("transactions")
  const recentTransactionsTable = transactionsContainer
    ? transactionsContainer.querySelector(".transactions-table")
    : null

  if (!qaMessages || !questionInput || !sendQuestion) {
    console.log("Chatbot elements not found")
    return
  }

  console.log("✅ Chatbot elements found, initializing...")

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
  let isImageProcessing = false  // Biến riêng cho image processing
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
      const dot = document.createElement("div")
      dot.style.width = "6px"
      dot.style.height = "6px"
      dot.style.borderRadius = "50%"
      dot.style.background = "var(--accent-color)"
      dot.style.animation = `typingDot 1.4s ease-in-out infinite ${i * 0.2}s`
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

  // Send message functionality with external API
  const sendChatMessage = async (externalMessage = null, showUserMessage = true) => {
    const message = externalMessage || questionInput.value.trim()
    
    console.log("sendChatMessage - Kiểm tra điều kiện:", { 
      message: message, 
      messageLength: message.length,
      isProcessing: isProcessing,
      isImageProcessing: isImageProcessing,
      showUserMessage: showUserMessage 
    })
    
    if (!message) {
      console.log("Không có message, thoát")
      return
    }
    
    if (isProcessing) {
      console.log("⚠️ isProcessing = true, thoát sendChatMessage")
      return
    }

    console.log("✅ sendChatMessage được gọi:", { message, showUserMessage })

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
    // Tìm phần qa-inputRGF
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
      if (!isImageProcessing) {
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

  // Hàm phân tích ảnh hóa đơn
  const analyzeBillImage = async (file) => {
    if (!file || isImageProcessing) return

    isImageProcessing = true

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
      
      // Hiển thị kết quả phân tích
      if (data.output_text) {
        console.log("Gửi kết quả đến chat API...")
        console.log("Output text:", data.output_text)
        
        try {
          // Gửi kết quả phân tích đến chat API
          await sendChatMessage(data.output_text, false)
          
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
      isImageProcessing = false
    }
  }

  // Event listeners
  sendQuestion.addEventListener("click", () => sendChatMessage())
  questionInput.addEventListener("keypress", function(e) {
    if (e.key === "Enter" && !isProcessing && !isImageProcessing) {
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
  
  console.log("✅ Chatbot setup completed!")

  // Function to display transactions detected by AI
  const displayTransactions = (transactions) => {
    if (!transactions || transactions.length === 0) return

    console.log("Displaying transactions:", transactions)

    // Create transaction display container
    const transactionDiv = document.createElement("div")
    transactionDiv.className = "ai-transactions"
    transactionDiv.style.cssText = `
      background: var(--card-background);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 16px;
      margin: 12px 0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    `

    const header = document.createElement("div")
    header.style.cssText = `
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 12px;
      color: var(--primary-color);
      font-weight: 600;
    `
    header.innerHTML = '<i class="fas fa-receipt" style="color: var(--accent-color);"></i> Giao dịch được phát hiện:'

    transactionDiv.appendChild(header)

    transactions.forEach((transaction, index) => {
      const transactionItem = document.createElement("div")
      transactionItem.style.cssText = `
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        background: var(--hover-color);
        border-radius: 8px;
        margin-bottom: 8px;
        border: 1px solid var(--border-color);
      `

      const leftInfo = document.createElement("div")
      leftInfo.innerHTML = `
        <div style="font-weight: 600; color: var(--primary-color);">${transaction.name || transaction.description || 'Giao dịch'}</div>
        <div style="font-size: 0.8rem; color: var(--secondary-color);">${transaction.category || 'Không phân loại'}</div>
      `

      const rightInfo = document.createElement("div")
      rightInfo.style.textAlign = "right"
      const amount = parseFloat(transaction.amount) || 0
      const isIncome = transaction.type === 'income'
      rightInfo.innerHTML = `
        <div style="font-weight: 600; color: ${isIncome ? 'var(--positive-color)' : 'var(--negative-color)'};">
          ${isIncome ? '+' : '-'}${new Intl.NumberFormat('vi-VN').format(Math.abs(amount))}₫
        </div>
        <div style="font-size: 0.8rem; color: var(--secondary-color);">${isIncome ? 'Thu nhập' : 'Chi tiêu'}</div>
      `

      transactionItem.appendChild(leftInfo)
      transactionItem.appendChild(rightInfo)
      transactionDiv.appendChild(transactionItem)
    })

    // Add action buttons
    const actionButtons = document.createElement("div")
    actionButtons.style.cssText = `
      display: flex;
      gap: 8px;
      margin-top: 12px;
      justify-content: flex-end;
    `

    const saveButton = document.createElement("button")
    saveButton.innerHTML = '<i class="fas fa-save"></i> Lưu tất cả'
    saveButton.style.cssText = `
      background: var(--accent-color);
      color: white;
      border: none;
      border-radius: 6px;
      padding: 8px 16px;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.2s ease;
    `
    saveButton.onmouseover = () => saveButton.style.background = 'var(--primary-color)'
    saveButton.onmouseout = () => saveButton.style.background = 'var(--accent-color)'

    const editButton = document.createElement("button")
    editButton.innerHTML = '<i class="fas fa-edit"></i> Chỉnh sửa'
    editButton.style.cssText = `
      background: var(--hover-color);
      color: var(--primary-color);
      border: 1px solid var(--border-color);
      border-radius: 6px;
      padding: 8px 16px;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.2s ease;
    `
    editButton.onmouseover = () => editButton.style.background = 'var(--border-color)'
    editButton.onmouseout = () => editButton.style.background = 'var(--hover-color)'

    // Add event listeners
    saveButton.addEventListener('click', () => saveTransactions(transactions))
    editButton.addEventListener('click', () => showEditTransactionsModal(transactions))

    actionButtons.appendChild(editButton)
    actionButtons.appendChild(saveButton)
    transactionDiv.appendChild(actionButtons)

    // Add to chat
    qaMessages.appendChild(transactionDiv)
    qaMessages.scrollTop = qaMessages.scrollHeight
  }

  // Function to save transactions
  const saveTransactions = async (transactions) => {
    if (!transactions || transactions.length === 0 || isProcessing) return

    isProcessing = true
    let savingMessage = null

    try {
      // Show saving message
      savingMessage = addMessageToChat("Đang lưu giao dịch...", "bot")

      // Limit transactions to avoid overload
      const transactionsToSave = transactions.slice(0, 5)

      console.log("Transactions to save:", transactionsToSave)

      // Send to server
      const response = await fetch("api/save-transactions.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ transactions: transactionsToSave }),
      })

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }

      const data = await response.json()

      if (savingMessage) {
        savingMessage.remove()
      }

      if (data.success) {
        addMessageToChat(`✅ Đã lưu thành công ${data.saved_count} giao dịch!`, "bot")
        
        // Gọi API xóa lịch sử chat sau khi save thành công
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
              console.log("✅ Lịch sử chat đã được xóa")
            } else {
              console.error("❌ Không thể xóa lịch sử chat")
            }
          })
          .catch((error) => {
            console.error("🔥 Lỗi khi xóa lịch sử chat:", error)
          })
        
        // Tự động cập nhật giao diện mà không reload
        setTimeout(() => {
          updateFinancialSummaryAfterSave(transactionsToSave)
          // addMessageToChat("💡 Đã cập nhật số liệu tài chính và giao dịch mới!", "bot")
        }, 1000)
      } else {
        throw new Error(data.error || "Unknown error")
      }
    } catch (error) {
      console.error("Error saving transactions:", error)
      if (savingMessage) {
        savingMessage.remove()
      }
      addMessageToChat("❌ Lỗi khi lưu giao dịch: " + error.message, "bot")
    } finally {
      isProcessing = false
    }
  }

  // Function to update financial summary after saving transactions
  const updateFinancialSummaryAfterSave = (savedTransactions) => {
    try {
      console.log("Updating financial summary with:", savedTransactions)
      
      // Tìm các thẻ tài chính trên trang
      const financeCards = document.querySelectorAll('.finance-card')
      
      if (financeCards.length >= 3) {
        // Lấy số liệu hiện tại
        const currentIncomeText = financeCards[0].querySelector('.card-amount')?.textContent || '0₫'
        const currentExpenseText = financeCards[1].querySelector('.card-amount')?.textContent || '0₫'
        const currentBalanceText = financeCards[2].querySelector('.card-amount')?.textContent || '0₫'
        
        // Chuyển đổi về số
        const currentIncome = parseFloat(currentIncomeText.replace(/[₫,]/g, '')) || 0
        const currentExpense = parseFloat(currentExpenseText.replace(/[₫,]/g, '')) || 0
        
        // Tính toán thay đổi từ giao dịch mới
        let incomeChange = 0
        let expenseChange = 0
        
        savedTransactions.forEach(transaction => {
          const amount = parseFloat(transaction.amount) || 0
          if (transaction.type === 'income') {
            incomeChange += amount
          } else {
            expenseChange += amount
          }
        })
        
        // Cập nhật số liệu mới
        const newIncome = currentIncome + incomeChange
        const newExpense = currentExpense + expenseChange
        const newBalance = newIncome - newExpense
        
        // Cập nhật giao diện với hiệu ứng
        updateCardWithAnimation(financeCards[0], newIncome, 'income')
        updateCardWithAnimation(financeCards[1], newExpense, 'expense')
        updateCardWithAnimation(financeCards[2], newBalance, 'balance')
        
        console.log("Financial summary updated:", {
          income: newIncome,
          expense: newExpense,
          balance: newBalance
        })
      }
      
      // Cập nhật danh sách giao dịch gần đây nếu có
      updateRecentTransactionsList(savedTransactions)
      
    } catch (error) {
      console.error("Error updating financial summary:", error)
    }
  }

  // Function to update card with animation
  const updateCardWithAnimation = (card, newValue, type) => {
    const amountElement = card.querySelector('.card-amount')
    if (!amountElement) return
    
    // Thêm hiệu ứng highlight
    card.style.transform = 'scale(1.02)'
    card.style.boxShadow = '0 8px 25px rgba(59,130,246,0.15)'
    
    // Cập nhật giá trị
    const formattedValue = new Intl.NumberFormat('vi-VN').format(Math.abs(newValue)) + '₫'
    amountElement.textContent = formattedValue
    
    // Đổi màu dựa trên loại
    if (type === 'balance') {
      amountElement.style.color = newValue >= 0 ? 'var(--positive-color)' : 'var(--negative-color)'
    }
    
    // Reset hiệu ứng sau 1 giây
    setTimeout(() => {
      card.style.transform = 'scale(1)'
      card.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)'
    }, 1000)
  }

  // Function to update recent transactions list
  const updateRecentTransactionsList = (newTransactions) => {
    const transactionsList = document.querySelector('.transactions-list')
    if (!transactionsList) return
    
    // Thêm giao dịch mới vào đầu danh sách
    newTransactions.forEach(transaction => {
      const transactionElement = createTransactionElement(transaction)
      transactionsList.insertBefore(transactionElement, transactionsList.firstChild)
    })
    
    // Giới hạn số lượng giao dịch hiển thị (giữ 10 giao dịch gần nhất)
    const allTransactions = transactionsList.querySelectorAll('.transaction-item')
    if (allTransactions.length > 10) {
      for (let i = 10; i < allTransactions.length; i++) {
        allTransactions[i].remove()
      }
    }
  }

  // Function to create transaction element
  const createTransactionElement = (transaction) => {
    const div = document.createElement('div')
    div.className = 'transaction-item'
    div.style.cssText = `
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px;
      border-bottom: 1px solid var(--border-color);
      background: var(--card-background);
      animation: slideInFromTop 0.5s ease-out;
    `
    
    const amount = parseFloat(transaction.amount) || 0
    const isIncome = transaction.type === 'income'
    const today = new Date().toLocaleDateString('vi-VN')
    
    div.innerHTML = `
      <div>
        <div style="font-weight: 600; color: var(--primary-color); margin-bottom: 2px;">
          ${transaction.name || transaction.description || 'Giao dịch mới'}
        </div>
        <div style="font-size: 0.8rem; color: var(--secondary-color);">
          ${transaction.category || 'Chung'} • ${today}
        </div>
      </div>
      <div style="text-align: right;">
        <div style="font-weight: 600; color: ${isIncome ? 'var(--positive-color)' : 'var(--negative-color)'};">
          ${isIncome ? '+' : '-'}${new Intl.NumberFormat('vi-VN').format(Math.abs(amount))}₫
        </div>
        <div style="font-size: 0.8rem; color: var(--secondary-color);">
          ${isIncome ? 'Thu nhập' : 'Chi tiêu'}
        </div>
      </div>
    `
    
    return div
  }

  // Function to show edit modal
  const showEditTransactionsModal = (transactions) => {
    console.log("Opening edit modal for transactions:", transactions)
    
    // Create modal overlay
    const modalOverlay = document.createElement('div')
    modalOverlay.id = 'editTransactionsModal'
    modalOverlay.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      backdrop-filter: blur(4px);
      display: flex;
      align-items: center;
      justify-content: center;
    `

    // Create modal content
    const modalContent = document.createElement('div')
    modalContent.style.cssText = `
      background: var(--card-background);
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
      max-width: 700px;
      width: 90%;
      max-height: 80vh;
      overflow-y: auto;
      position: relative;
    `

    // Modal header
    const modalHeader = document.createElement('div')
    modalHeader.style.cssText = `
      padding: 20px 24px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: var(--hover-color);
      border-radius: 12px 12px 0 0;
    `
    modalHeader.innerHTML = `
      <h2 style="margin: 0; color: var(--primary-color); font-size: 1.3rem; font-weight: 700;">
        <i class="fas fa-edit" style="color: var(--accent-color); margin-right: 8px;"></i>
        Chỉnh sửa giao dịch
      </h2>
      <button class="close-modal" style="background: none; border: none; font-size: 1.5rem; color: var(--secondary-color); cursor: pointer; padding: 4px; border-radius: 4px; transition: all 0.2s ease;">
        &times;
      </button>
    `

    // Modal body
    const modalBody = document.createElement('div')
    modalBody.style.cssText = `
      padding: 24px;
    `

    // Create form for each transaction
    let formHTML = ''
    transactions.forEach((transaction, index) => {
      const amount = parseFloat(transaction.amount) || 0
      const isIncome = transaction.type === 'income'
      
      formHTML += `
        <div class="transaction-edit-item" style="
          background: var(--hover-color);
          border: 1px solid var(--border-color);
          border-radius: 8px;
          padding: 16px;
          margin-bottom: 16px;
        ">
          <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 12px;">
            <h4 style="margin: 0; color: var(--primary-color); font-size: 1rem;">
              Giao dịch ${index + 1}
            </h4>
            <span style="
              padding: 4px 8px;
              border-radius: 12px;
              font-size: 0.8rem;
              font-weight: 500;
              background: ${isIncome ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'};
              color: ${isIncome ? 'var(--positive-color)' : 'var(--negative-color)'};
            ">
              ${isIncome ? 'Thu nhập' : 'Chi tiêu'}
            </span>
          </div>
          
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div>
              <label style="display: block; margin-bottom: 4px; color: var(--primary-color); font-weight: 500; font-size: 0.9rem;">
                Tên giao dịch:
              </label>
              <input type="text" 
                     value="${(transaction.name || transaction.description || '').replace(/"/g, '&quot;')}" 
                     data-field="name" 
                     data-index="${index}"
                     style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--card-background); color: var(--primary-color); font-size: 0.9rem;">
            </div>
            
            <div>
              <label style="display: block; margin-bottom: 4px; color: var(--primary-color); font-weight: 500; font-size: 0.9rem;">
                Số tiền:
              </label>
              <input type="number" 
                     value="${Math.abs(amount)}" 
                     data-field="amount" 
                     data-index="${index}"
                     style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--card-background); color: var(--primary-color); font-size: 0.9rem;">
            </div>
          </div>
          
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
              <label style="display: block; margin-bottom: 4px; color: var(--primary-color); font-weight: 500; font-size: 0.9rem;">
                Loại:
              </label>
              <select data-field="type" 
                      data-index="${index}"
                      style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--card-background); color: var(--primary-color); font-size: 0.9rem;">
                <option value="income" ${isIncome ? 'selected' : ''}>Thu nhập</option>
                <option value="expense" ${!isIncome ? 'selected' : ''}>Chi tiêu</option>
              </select>
            </div>
            
            <div>
              <label style="display: block; margin-bottom: 4px; color: var(--primary-color); font-weight: 500; font-size: 0.9rem;">
                Danh mục:
              </label>
              <input type="text" 
                     value="${(transaction.category || 'Chung').replace(/"/g, '&quot;')}" 
                     data-field="category" 
                     data-index="${index}"
                     style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--card-background); color: var(--primary-color); font-size: 0.9rem;">
            </div>
          </div>
          
          <div style="margin-top: 12px; text-align: right;">
            <button type="button" 
                    onclick="removeTransaction(${index})"
                    style="background: var(--negative-color); color: white; border: none; border-radius: 6px; padding: 6px 12px; font-size: 0.8rem; cursor: pointer; transition: all 0.2s ease;">
              <i class="fas fa-trash"></i> Xóa
            </button>
          </div>
        </div>
      `
    })

    modalBody.innerHTML = formHTML

    // Modal footer
    const modalFooter = document.createElement('div')
    modalFooter.style.cssText = `
      padding: 20px 24px;
      border-top: 1px solid var(--border-color);
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      background: var(--hover-color);
      border-radius: 0 0 12px 12px;
    `
    modalFooter.innerHTML = `
      <button type="button" class="cancel-modal" style="
        background: var(--hover-color);
        color: var(--primary-color);
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 10px 20px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
      ">
        Hủy
      </button>
      <button type="button" id="saveEditedTransactions" style="
        background: var(--accent-color);
        color: white;
        border: none;
        border-radius: 6px;
        padding: 10px 20px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
      ">
        <i class="fas fa-save"></i> Lưu thay đổi
      </button>
    `

    // Assemble modal
    modalContent.appendChild(modalHeader)
    modalContent.appendChild(modalBody)
    modalContent.appendChild(modalFooter)
    modalOverlay.appendChild(modalContent)

    // Add to document
    document.body.appendChild(modalOverlay)

    // Event listeners
    const closeModal = () => {
      modalOverlay.remove()
    }

    modalHeader.querySelector('.close-modal').addEventListener('click', closeModal)
    modalFooter.querySelector('.cancel-modal').addEventListener('click', closeModal)
    
    // Close on overlay click
    modalOverlay.addEventListener('click', (e) => {
      if (e.target === modalOverlay) {
        closeModal()
      }
    })

    // Save edited transactions
    modalFooter.querySelector('#saveEditedTransactions').addEventListener('click', () => {
      const editedTransactions = []
      const transactionItems = modalBody.querySelectorAll('.transaction-edit-item')
      
      transactionItems.forEach((item, index) => {
        const inputs = item.querySelectorAll('input, select')
        const transaction = { ...transactions[index] }
        
        inputs.forEach(input => {
          const field = input.getAttribute('data-field')
          const value = input.value.trim()
          
          if (field === 'name') {
            transaction.name = value
            transaction.description = value
          } else if (field === 'amount') {
            transaction.amount = parseFloat(value) || 0
          } else if (field === 'type') {
            transaction.type = value
          } else if (field === 'category') {
            transaction.category = value
          }
        })
        
        editedTransactions.push(transaction)
      })
      
      console.log('Edited transactions:', editedTransactions)
      closeModal()
      
      // Save the edited transactions
      saveTransactions(editedTransactions)
    })

    // Global function to remove transaction
    window.removeTransaction = (index) => {
      const item = modalBody.querySelector(`[data-index="${index}"]`).closest('.transaction-edit-item')
      if (item) {
        item.remove()
        // Update remaining indices
        const remainingItems = modalBody.querySelectorAll('.transaction-edit-item')
        remainingItems.forEach((item, newIndex) => {
          const inputs = item.querySelectorAll('input, select')
          inputs.forEach(input => {
            input.setAttribute('data-index', newIndex)
          })
          const title = item.querySelector('h4')
          if (title) {
            title.textContent = `Giao dịch ${newIndex + 1}`
          }
          const removeBtn = item.querySelector('button[onclick]')
          if (removeBtn) {
            removeBtn.setAttribute('onclick', `removeTransaction(${newIndex})`)
          }
        })
      }
    }
  }
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

// Add CSS animations for typing indicator
const style = document.createElement('style')
style.textContent = `
  @keyframes typingDot {
    0%, 60%, 100% {
      transform: translateY(0);
      opacity: 0.4;
    }
    30% {
      transform: translateY(-10px);
      opacity: 1;
    }
  }
  
  @keyframes slideInFromTop {
    0% {
      transform: translateY(-20px);
      opacity: 0;
    }
    100% {
      transform: translateY(0);
      opacity: 1;
    }
  }
  
  @keyframes highlightCard {
    0% {
      transform: scale(1);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    50% {
      transform: scale(1.02);
      box-shadow: 0 8px 25px rgba(59,130,246,0.15);
    }
    100% {
      transform: scale(1);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
  }
  
  .send-button-active {
    background: var(--accent-color) !important;
    transform: scale(1.1);
  }
  
  .finance-card {
    transition: all 0.3s ease;
  }
  
  .transaction-item {
    transition: all 0.3s ease;
  }
  
  .transaction-item:hover {
    background: var(--hover-color) !important;
  }
`
document.head.appendChild(style)

