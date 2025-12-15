<?php
namespace App\Models;

use App\Core\ConnectDB;
use PDO;

class Wallet
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    // Lấy thông tin ví của user
    public function getWallet($userId, $jarCode)
    {
        $stmt = $this->db->prepare("SELECT * FROM user_wallets WHERE user_id = ? AND jar_code = ?");
        $stmt->execute([$userId, $jarCode]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Nếu chưa có thì tạo mới
        if (!$wallet) {
            $this->db->prepare("INSERT INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, 0)")
                     ->execute([$userId, $jarCode]);
            return ['balance' => 0, 'jar_code' => $jarCode];
        }
        return $wallet;
    }

    // Lấy tất cả ví của user (Trả về mảng danh sách)
    public function getAllWallets($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM user_wallets WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // [MỚI] Lấy số dư dạng Key-Value ['nec' => 100, 'play' => 200...]
    public function getWalletBalances($userId)
    {
        $wallets = $this->getAllWallets($userId);
        $balances = [];
        foreach ($wallets as $w) {
            $balances[$w['jar_code']] = $w['balance'];
        }
        return $balances;
    }

    // Cộng tiền vào ví
    public function addMoney($userId, $jarCode, $amount)
    {
        $this->getWallet($userId, $jarCode); // Đảm bảo ví tồn tại
        $sql = "UPDATE user_wallets SET balance = balance + ? WHERE user_id = ? AND jar_code = ?";
        return $this->db->prepare($sql)->execute([$amount, $userId, $jarCode]);
    }

    // Trừ tiền khỏi ví
    public function subtractMoney($userId, $jarCode, $amount)
    {
        $sql = "UPDATE user_wallets SET balance = balance - ? WHERE user_id = ? AND jar_code = ?";
        return $this->db->prepare($sql)->execute([$amount, $userId, $jarCode]);
    }
}