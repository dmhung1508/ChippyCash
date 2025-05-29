import datetime
import base64
import hashlib
import typing
import requests
import json
from .capcha_ocr import CapchaOCR, CapchaProcessing
from .wasm_helper import wasm_encrypt

headers_default = {
    'Cache-Control': 'max-age=0',
    'Accept': 'application/json, text/plain, */*',
    'Authorization': 'Basic RU1CUkVUQUlMV0VCOlNEMjM0ZGZnMzQlI0BGR0AzNHNmc2RmNDU4NDNm',
    'User-Agent': "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36",
    "Origin": "https://online.mbbank.com.vn",
    "Referer": "https://online.mbbank.com.vn/pl/login?returnUrl=%2F",
    "App": "MB_WEB",
    "Sec-Ch-Ua": '"Not.A/Brand";v="8", "Chromium";v="134", "Google Chrome";v="134"',
    "Sec-Ch-Ua-Mobile": "?0",
    "Sec-Ch-Ua-Platform": '"Windows"',
    "Sec-Fetch-Dest": "empty",
    "Sec-Fetch-Mode": "cors",
    "Sec-Fetch-Site": "same-origin",
}


def get_now_time():
    now = datetime.datetime.now()
    microsecond = int(now.strftime("%f")[:2])
    return now.strftime(f"%Y%m%d%H%M{microsecond}")


class MBBankError(Exception):
    def __init__(self, err_out):
        self.code = err_out['responseCode']
        self.message = err_out['message']
        super().__init__(f"{err_out['responseCode']} | {err_out['message']}")


class MBBank:
    """Core class

    Attributes:
        deviceIdCommon (str): Device id common
        sessionId (str or None): Current Session id

    Args:
        username (str): MBBank Account Username
        password (str): MBBank Account Password
        proxy (str, optional): Proxy url. Example: "http://127.0.0.1:8080". Defaults to None.
        ocr_class (CapchaProcessing, optional): CapchaProcessing class. Defaults to TesseractOCR().
    """
    deviceIdCommon = f'i1vzyjp5-mbib-0000-0000-{get_now_time()}'
    FPR = "c7a1beebb9400375bb187daa33de9659"

    def __init__(self, *, username, password, proxy=None, ocr_class=None):
        self.__userid = username
        self.__password = password
        self.__wasm_cache = None
        if proxy is not None:
            proxy_protocol = proxy.split("://")[0]
            self.proxy = {proxy_protocol: proxy}
        else:
            self.proxy = {}
        self.ocr_class = CapchaOCR()
        if ocr_class is not None:
            if not isinstance(ocr_class, CapchaProcessing):
                raise ValueError("ocr_class must be instance of CapchaProcessing")
            self.ocr_class = ocr_class
        self.sessionId = None
        self._userinfo = None
        self._temp = {}

    def _req(self, url, *, json=None, headers=None):
        if headers is None:
            headers = {}
        if json is None:
            json = {}
        while True:
            if self.sessionId is None:
                self._authenticate()
            rid = f"{self.__userid}-{get_now_time()}"
            json_data = {
                'sessionId': self.sessionId if self.sessionId is not None else "",
                'refNo': rid,
                'deviceIdCommon': self.deviceIdCommon,
            }
            json_data.update(json)
            headers.update(headers_default)
            headers["X-Request-Id"] = rid
            headers["RefNo"] = rid
            headers["DeviceId"] = self.deviceIdCommon
            with requests.Session() as s:
                with s.post(url, headers=headers, json=json_data,
                            proxies=self.proxy) as r:
                    data_out = r.json()
            if data_out["result"] is None:
                self.getBalance()
            elif data_out["result"]["ok"]:
                data_out.pop("result", None)
                break
            elif data_out["result"]["responseCode"] == "GW200":
                self._authenticate()
            else:
                err_out = data_out["result"]
                raise MBBankError(err_out)
        return data_out

    def _get_wasm_file(self):
        if self.__wasm_cache is not None:
            return self.__wasm_cache
        file_data = requests.get("https://online.mbbank.com.vn/assets/wasm/main.wasm",
                                 proxies=self.proxy).content
        self.__wasm_cache = file_data
        return file_data

    def _authenticate(self):
        while True:
            self._userinfo = None
            self.sessionId = None
            self._temp = {}
            rid = f"{self.__userid}-{get_now_time()}"
            json_data = {
                'sessionId': "",
                'refNo': rid,
                'deviceIdCommon': self.deviceIdCommon,
            }
            headers = headers_default.copy()
            headers["X-Request-Id"] = rid
            headers["Deviceid"] = self.deviceIdCommon
            headers["Refno"] = rid
            with requests.Session() as s:
                with s.post("https://online.mbbank.com.vn/retail-web-internetbankingms/getCaptchaImage",
                            headers=headers, json=json_data,
                            proxies=self.proxy) as r:
                    data_out = r.json()
            img_bytes = base64.b64decode(data_out["imageString"])
            text = self.ocr_class.process_image(img_bytes)
            payload = {
                "userId": self.__userid,
                "password": hashlib.md5(self.__password.encode()).hexdigest(),
                "captcha": text,
                'sessionId': "",
                'refNo': f'{self.__userid}-{get_now_time()}',
                'deviceIdCommon': self.deviceIdCommon,
                "ibAuthen2faString": self.FPR,
            }
            wasm_bytes = self._get_wasm_file()
            dataEnc = wasm_encrypt(wasm_bytes, payload)
            with requests.Session() as s:
                with s.post("https://online.mbbank.com.vn/retail_web/internetbanking/doLogin",
                            headers=headers_default, json={"dataEnc": dataEnc},
                            proxies=self.proxy) as r:
                    data_out = r.json()
            if data_out["result"]["ok"]:
                self.sessionId = data_out["sessionId"]
                self._userinfo = data_out
                return
            elif data_out["result"]["responseCode"] == "GW283":
                pass
            else:
                err_out = data_out["result"]
                raise Exception(f"{err_out['responseCode']} | {err_out['message']}")

    def getTransactionAccountHistory(self, *, accountNo: str = None, from_date: datetime.datetime,
                                     to_date: datetime.datetime):
        """
        Get account transaction history

        Args:
            accountNo (str, optional): Sub account number Defaults to Main Account number.
            from_date (datetime.datetime): transaction from date
            to_date (datetime.datetime): transaction to date

        Returns:
            success (dict): account transaction history

        Raises:
            MBBankError: if api response not ok
        """
        if self._userinfo is None:
            self._authenticate()
        json_data = {
            'accountNo': self.__userid if accountNo is None else accountNo,
            'fromDate': from_date.strftime("%d/%m/%Y"),
            'toDate': to_date.strftime("%d/%m/%Y"),  # max 3 months
        }
        data_out = self._req(
            "https://online.mbbank.com.vn/api/retail-transactionms/transactionms/get-account-transaction-history",
            json=json_data)
        return data_out

    def getBalance(self):
        """
        Get all main account and subaccount balance

        Returns:
            success (dict): list account balance

        Raises:
            MBBankError: if api response not ok
        """
        if self._userinfo is None:
            self._authenticate()
        data_out = self._req("https://online.mbbank.com.vn/api/retail-web-accountms/getBalance")
        return data_out

    def getBalanceLoyalty(self):
        """
        Get Account loyalty rank and Member loyalty point

        Returns:
            success (dict): loyalty point

        Raises:
            MBBankError: if api response not ok
        """
        data_out = self._req("https://online.mbbank.com.vn/api/retail_web/loyalty/getBalanceLoyalty")
        return data_out

    def getInterestRate(self, currency: str = "VND"):
        """
        Get saving interest rate

        Args:
            currency (str, optional): currency ISO 4217 format. Defaults to "VND" (Vietnam Dong).

        Returns:
            success (dict): interest rate

        Raises:
            MBBankError: if api response not ok
        """
        json_data = {
            "productCode": "TIENGUI.KHN.EMB",
            "currency": currency,
        }
        data_out = self._req("https://online.mbbank.com.vn/api/retail_web/saving/getInterestRate", json=json_data)
        return data_out

    def getFavorBeneficiaryList(self, *, transactionType: typing.Literal["TRANSFER", "PAYMENT"],
                                searchType: typing.Literal["MOST", "LATEST"]):
        """
        Get all favor or most transfer beneficiary list from your account

        Args:
            transactionType (Literal["TRANSFER", "PAYMENT"]): transaction type
            searchType (Literal["MOST", "LATEST"]): search type

        Returns:
            success (dict): favor beneficiary list

        Raises:
            MBBankError: if api response not ok
        """
        json_data = {
            "transactionType": transactionType,
            "searchType": searchType
        }
        data_out = self._req(
            "https://online.mbbank.com.vn/api/retail_web/internetbanking/getFavorBeneficiaryList", json=json_data)
        return data_out

    def getCardList(self):
        """
        Get all card list from your account

        Returns:
            success (dict): card list

        Raises:
            MBBankError: if api response not ok
        """
        data_out = self._req("https://online.mbbank.com.vn/api/retail_web/card/getList")
        return data_out

    def getSavingList(self):
        """
        Get all saving list from your account

        Returns:
            success (dict): saving list

        Raises:
            MBBankError: if api response not ok
        """
        data_out = self._req("https://online.mbbank.com.vn/api/retail_web/saving/getList")
        return data_out

    def getLoanList(self):
        """
        Get all loan list from your account

        Returns:
            success (dict): loan list

        Raises:
            MBBankError: if api response not ok
        """
        data_out = self._req("https://online.mbbank.com.vn/api/retail-web-onlineloanms/loan/getList")
        return data_out

    def getCardTransactionHistory(self, cardNo: str, from_date: datetime.datetime, to_date: datetime.datetime):
        """
        Get card transaction history

        Args:
            cardNo (str): card number get from getCardList
            from_date (datetime.datetime): from date
            to_date (datetime.datetime): to date

        Returns:
            success (dict): card transaction history

        Raises:
            MBBankError: if api response not ok
        """
        json_data = {
            "accountNo": cardNo,
            "fromDate": from_date.strftime("%d/%m/%Y"),
            "toDate": to_date.strftime("%d/%m/%Y"),  # max 3 months
            "historyNumber": "",
            "historyType": "DATE_RANGE",
            "type": "CARD",
        }
        data_out = self._req("https://online.mbbank.com.vn/api/retail_web/common/getTransactionHistory", json=json_data)
        return data_out

    def getBankList(self):
        """
        Get transfer all bank list

        Returns:
            success (dict): bank list

        Raises:
            MBBankError: if api response not ok
        """
        data_out = self._req("https://online.mbbank.com.vn/api/retail_web/common/getBankList")
        return data_out

    def getAccountByPhone(self, phone: str):
        """
        Get transfer account info by phone (MBank internal account only)

        Args:
            phone (str): MBBank account phone number

        Returns:
            success (dict): account info

        """
        json_data = {
            "phone": phone
        }
        data_out = self._req("https://online.mbbank.com.vn/api/retail_web/common/getAccountByPhone", json=json_data)
        return data_out

    def userinfo(self):
        """
        Get current user info

        Returns:
            success (dict): user info

        Raises:
            MBBankError: if api response not ok
        """
        if self._userinfo is None:
            self._authenticate()
        else:
            self.getBalance()
        return self._userinfo

    def getAccountNumbers(self):
        """
        Get all account numbers from user info

        Returns:
            success (list): list of account numbers

        Raises:
            MBBankError: if api response not ok
        """
        if self._userinfo is None:
            self._authenticate()
        
        acct_list = self._userinfo.get('cust', {}).get('acct_list', {})
        return list(acct_list.keys())

    def getAccountDetails(self):
        """
        Get detailed account information for all accounts

        Returns:
            success (dict): detailed account information

        Raises:
            MBBankError: if api response not ok
        """
        if self._userinfo is None:
            self._authenticate()
        
        return self._userinfo.get('cust', {}).get('acct_list', {})

    def getAccountBalanceInfo(self):
        """
        Get account numbers and current balance information
        
        Returns:
            success (dict): account balance information with structure:
            {
                'accounts': [
                    {
                        'acctNo': str,
                        'acctName': str,
                        'currentBalance': str,
                        'ccyCd': str,
                        'category': str,
                        ...
                    }
                ],
                'totalBalance': str,
                'currency': str,
                'accountCount': int
            }

        Raises:
            MBBankError: if api response not ok
        """
        balance_data = self.getBalance()
        
        if 'acct_list' not in balance_data:
            return {
                'accounts': [],
                'totalBalance': '0',
                'currency': 'VND',
                'accountCount': 0,
                'error': 'No account list found in response'
            }
        
        accounts = []
        total_balance = 0
        
        for account in balance_data['acct_list']:
            current_balance = account.get('currentBalance', '0')
            # Chuyển đổi số dư thành số để tính tổng
            try:
                balance_amount = float(current_balance.replace(',', '')) if current_balance else 0
                total_balance += balance_amount
            except (ValueError, AttributeError):
                balance_amount = 0
            
            account_info = {
                'acctNo': account.get('acctNo', ''),
                'acctName': account.get('acctNm', account.get('acctAlias', '')),
                'currentBalance': current_balance,
                'balanceFormatted': f"{balance_amount:,.0f}" if balance_amount > 0 else "0",
                'ccyCd': account.get('ccyCd', 'VND'),
                'category': account.get('category', ''),
                'subCategory': account.get('subCategory', ''),
                'acctTypCd': account.get('acctTypCd', ''),
                'isCard': account.get('isCard'),
                'cardNumber': account.get('cardNumber'),
                'cardType': account.get('cardType')
            }
            accounts.append(account_info)
        
        result = {
            'accounts': accounts,
            'totalBalance': f"{total_balance:,.0f}",
            'totalBalanceRaw': total_balance,
            'currency': balance_data.get('currencyEquivalent', 'VND'),
            'accountCount': len(accounts),
            'totalBalanceEquivalent': balance_data.get('totalBalanceEquivalent', str(int(total_balance))),
            'raw_data': balance_data  # Thêm dữ liệu gốc để tham khảo
        }
        
        return result

    def getAllAccountInfo(self, *, from_date: datetime.datetime = None, to_date: datetime.datetime = None, 
                          include_transaction_history: bool = True, include_card_info: bool = True,
                          include_saving_info: bool = True, include_loan_info: bool = True):
        """
        Get comprehensive account information including balance, transaction history, cards, savings, loans

        Args:
            from_date (datetime.datetime, optional): Transaction history from date. Defaults to 30 days ago.
            to_date (datetime.datetime, optional): Transaction history to date. Defaults to today.
            include_transaction_history (bool, optional): Include transaction history. Defaults to True.
            include_card_info (bool, optional): Include card information. Defaults to True.
            include_saving_info (bool, optional): Include saving information. Defaults to True.
            include_loan_info (bool, optional): Include loan information. Defaults to True.

        Returns:
            success (dict): comprehensive account information

        Raises:
            MBBankError: if api response not ok
        """
        if self._userinfo is None:
            self._authenticate()
        
        # Thiết lập ngày mặc định nếu không được cung cấp
        if to_date is None:
            to_date = datetime.datetime.now()
        if from_date is None:
            from_date = to_date - datetime.timedelta(days=30)  # 30 ngày trước
            
        result = {
            'user_info': self.userinfo(),
            'account_numbers': self.getAccountNumbers(),
            'account_details': self.getAccountDetails(),
            'balance': self.getBalance(),
        }
        
        # Lấy lịch sử giao dịch cho tất cả tài khoản
        if include_transaction_history:
            result['transaction_history'] = {}
            account_numbers = self.getAccountNumbers()
            for acc_no in account_numbers:
                try:
                    history = self.getTransactionAccountHistory(
                        accountNo=acc_no,
                        from_date=from_date,
                        to_date=to_date
                    )
                    result['transaction_history'][acc_no] = history
                except Exception as e:
                    result['transaction_history'][acc_no] = {'error': str(e)}
        
        # Lấy thông tin thẻ
        if include_card_info:
            try:
                result['cards'] = self.getCardList()
                # Lấy lịch sử giao dịch thẻ nếu có thẻ
                if 'cardList' in result['cards'] and result['cards']['cardList']:
                    result['card_transaction_history'] = {}
                    for card in result['cards']['cardList']:
                        try:
                            card_history = self.getCardTransactionHistory(
                                cardNo=card['cardNo'],
                                from_date=from_date,
                                to_date=to_date
                            )
                            result['card_transaction_history'][card['cardNo']] = card_history
                        except Exception as e:
                            result['card_transaction_history'][card['cardNo']] = {'error': str(e)}
            except Exception as e:
                result['cards'] = {'error': str(e)}
        
        # Lấy thông tin tiết kiệm
        if include_saving_info:
            try:
                result['savings'] = self.getSavingList()
            except Exception as e:
                result['savings'] = {'error': str(e)}
        
        # Lấy thông tin vay
        if include_loan_info:
            try:
                result['loans'] = self.getLoanList()
            except Exception as e:
                result['loans'] = {'error': str(e)}
        
        # Lấy thông tin điểm thưởng
        try:
            result['loyalty'] = self.getBalanceLoyalty()
        except Exception as e:
            result['loyalty'] = {'error': str(e)}
        
        # Lấy danh sách ngân hàng
        try:
            result['bank_list'] = self.getBankList()
        except Exception as e:
            result['bank_list'] = {'error': str(e)}
        
        # Lấy lãi suất tiết kiệm
        try:
            result['interest_rate'] = self.getInterestRate()
        except Exception as e:
            result['interest_rate'] = {'error': str(e)}
        
        # Thêm thời gian lấy dữ liệu
        result['data_timestamp'] = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        result['query_period'] = {
            'from_date': from_date.strftime("%Y-%m-%d"),
            'to_date': to_date.strftime("%Y-%m-%d")
        }
        
        return result

def getBank(username: str, password: str, proxy: str = None):
    """
    Get comprehensive bank account information including balance and transaction history
    
    Args:
        username (str): MBBank account username
        password (str): MBBank account password
        proxy (str, optional): Proxy URL if needed
    
    Returns:
        dict: JSON response with bank account information
    """
    try:
        mb = MBBank(username=username, password=password, proxy=proxy)
        
        # Lấy thông tin tài khoản và số dư
        balance_info = mb.getAccountBalanceInfo()
        
        # Tìm tài khoản có số dư cao nhất để lấy lịch sử giao dịch
        max_balance_account = None
        if balance_info['accounts']:
            max_balance_account = max(balance_info['accounts'], 
                                    key=lambda x: float(x['currentBalance'].replace(',', '')) if x['currentBalance'] else 0)
        
        # Lấy lịch sử giao dịch của tất cả tài khoản (30 ngày gần nhất)
        to_date = datetime.datetime.now()
        from_date = to_date - datetime.timedelta(days=7)
        
        all_transactions = []
        account_numbers = mb.getAccountNumbers()
        
        for account_no in account_numbers:
            try:
                transaction_history = mb.getTransactionAccountHistory(
                    accountNo=account_no,
                    from_date=from_date,
                    to_date=to_date
                )
                
                if 'transactionHistoryList' in transaction_history:
                    transactions = transaction_history['transactionHistoryList']
                    
                    # Lọc và chỉ lấy các field cần thiết
                    for trans in transactions:
                        filtered_trans = {
                            'transactionDate': trans.get('transactionDate', ''),
                            'accountNo': trans.get('accountNo', account_no),
                            'creditAmount': trans.get('creditAmount', '0'),
                            'debitAmount': trans.get('debitAmount', '0'),
                            'description': trans.get('description', ''),
                            'benAccountName': trans.get('benAccountName', ''),
                            'bankName': trans.get('bankName', ''),
                            'benAccountNo': trans.get('benAccountNo', '')
                        }
                        all_transactions.append(filtered_trans)
                        
            except Exception as e:
                # Thêm thông tin lỗi cho tài khoản này
                error_trans = {
                    'transactionDate': '',
                    'accountNo': account_no,
                    'creditAmount': '0',
                    'debitAmount': '0',
                    'description': f'Lỗi lấy dữ liệu: {str(e)}',
                    'benAccountName': '',
                    'bankName': '',
                    'benAccountNo': ''
                }
                all_transactions.append(error_trans)
        
        # Sắp xếp theo ngày giao dịch (mới nhất trước)
        all_transactions.sort(key=lambda x: x['transactionDate'], reverse=True)
        
        # Tính thống kê tổng hợp
        total_credit = sum(float(t['creditAmount']) for t in all_transactions if t['creditAmount'] and t['creditAmount'] != '0')
        total_debit = sum(float(t['debitAmount']) for t in all_transactions if t['debitAmount'] and t['debitAmount'] != '0')
        
        transaction_data = {
            'period': {
                'from_date': from_date.strftime('%Y-%m-%d'),
                'to_date': to_date.strftime('%Y-%m-%d')
            },
            'summary': {
                'total_transactions': len(all_transactions),
                'total_accounts': len(account_numbers),
                'total_credit': total_credit,
                'total_debit': total_debit,
                'net_amount': total_credit - total_debit
            },
            'transactions': all_transactions
        }
        
        # Tạo response JSON hoàn chỉnh
        result = {
            'success': True,
            'timestamp': datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            'data': {
                'balance_info': balance_info,
                'transaction_data': transaction_data,
                'account_numbers': mb.getAccountNumbers()
            }
        }
        
        return result
        
    except Exception as e:
        error_result = {
            'success': False,
            'timestamp': datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            'error': str(e),
            'data': None
        }
        return error_result

def main():
    """Main function to demonstrate MBBank API usage with JSON output"""
    # Sử dụng hàm getBank mới

    # Output JSON
    print(json.dumps(result, ensure_ascii=False, indent=2))

if __name__ == "__main__":
    main()