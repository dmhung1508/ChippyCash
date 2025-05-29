import mysql.connector

# Thông tin kết nối MySQL trên cPanel
db_config = {
    "host": "localhost",  # Hoặc có thể dùng 'znsdpzlo.cloudfly.vn'
    "user": "root",
    "password": "hung1234",
    "database": "chippy",  # Thay bằng tên database thực tế
}
def get_connection():
    """Tạo và trả về kết nối đến cơ sở dữ liệu"""
    try:
        conn = mysql.connector.connect(**db_config)
        return conn
    except mysql.connector.Error as e:
        print(f"❌ Lỗi kết nối: {e}")
        return None
     
def get_user_id_by_username(username):
    """Lấy ID của user dựa trên username/email"""
    conn = get_connection()
    if not conn:
        return None
    
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id FROM users WHERE email = %s OR username = %s OR name = %s", 
                      (username, username, username))
        user = cursor.fetchone()
        return user['id'] if user else None
    except mysql.connector.Error as e:
        print(f"❌ Lỗi truy vấn: {e}")
        return None
    finally:
        cursor.close()
        conn.close()
def get_user_id_by_id(id):
    """Lấy ID của user dựa trên username/email"""
    conn = get_connection()
    if not conn:
        return None

    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id FROM users WHERE id = %s", (id,))
        user = cursor.fetchone()
        return user['id'] if user else None
    except mysql.connector.Error as e:
        print(f"❌ Lỗi truy vấn: {e}")
        return None
def get_username_by_id(id):
    """Lấy username của user dựa trên id"""
    conn = get_connection()
    if not conn:
        return None
    
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT username FROM users WHERE id = %s", (id,))
        user = cursor.fetchone()
        return user['username'] if user else None
    except mysql.connector.Error as e:
        print(f"❌ Lỗi truy vấn: {e}")
        return None
def get_categories_by_username(username):
    """Lấy tất cả categories của một user dựa trên username"""
    user_id = get_user_id_by_username(username)
    if not user_id:
        return []
    
    conn = get_connection()
    if not conn:
        return []
    
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT id, name, description, type, created_at, updated_at 
            FROM categories 
            WHERE user_id = %s
            ORDER BY type, name
        """, (user_id,))
        categories = cursor.fetchall()
        
        # Format the categories as per the required output
        formatted_output = ""
        
        # Group by type
        income_categories = [c for c in categories if c['type'] == 'income']
        expense_categories = [c for c in categories if c['type'] == 'expense']
        
        # Format income categories
        if income_categories:
            formatted_output += "  + Khoản thu:\n"
            for category in income_categories:
                desc = category['description'] or f"Thu nhập từ {category['name'].lower()}."
                formatted_output += f"    + {category['name']}: {desc}\n"
        
        # Format expense categories
        if expense_categories:
            formatted_output += "  + Khoản chi:\n"
            for category in expense_categories:
                desc = category['description'] or f"Tiền {category['name'].lower()} hàng tháng."
                formatted_output += f"    + {category['name']}: {desc}\n"
                
        return formatted_output
    except mysql.connector.Error as e:
        print(f"❌ Lỗi truy vấn: {e}")
        return []
    finally:
        cursor.close()
        conn.close()

def get_categories_by_type(username, type_filter):
    """
    Lấy categories của một user theo loại (income hoặc expense)
    
    Args:
        username: Tên đăng nhập hoặc email của người dùng
        type_filter: Loại category ('income' hoặc 'expense')
    """
    if type_filter not in ['income', 'expense']:
        return []
        
    user_id = get_user_id_by_username(username)
    if not user_id:
        return []
    
    conn = get_connection()
    if not conn:
        return []
    
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT id, name, description, type, created_at, updated_at 
            FROM categories 
            WHERE user_id = %s AND type = %s
            ORDER BY name
        """, (user_id, type_filter))
        categories = cursor.fetchall()
        return categories
    except mysql.connector.Error as e:
        print(f"❌ Lỗi truy vấn: {e}")
        return []
    finally:
        cursor.close()
        conn.close()

# Ví dụ sử dụng
# Ví dụ sử dụng
if __name__ == "__main__":
    try:
        # Kết nối đến MySQL
        conn = mysql.connector.connect(**db_config)

        if conn.is_connected():
            print("✅ Kết nối thành công đến MySQL trên cPanel!")

        # Tạo một con trỏ để thực hiện truy vấn
        cursor = conn.cursor()
        cursor.execute("SHOW TABLES")

        print("📌 Danh sách bảng trong database:")
        for table in cursor.fetchall():
            print(table)
        print(get_username_by_id("4"))
        print("\n📋 Tất cả categories của demo_user:")
        print(get_categories_by_username(get_username_by_id("4")))
        
        print("\n💰 Income categories của demo_user:")
        print(get_categories_by_type("1", "income"))
        
        print("\n💸 Expense categories của demo_user:")
        print(get_categories_by_type("1", "expense"))
        
        # Đóng kết nối
        cursor.close()
        conn.close()
    except mysql.connector.Error as e:
        print(f"❌ Lỗi kết nối: {e}")