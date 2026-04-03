<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class PrivateMessage extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['sender_id', 'recipient_id', 'body', 'read_at'];

    public function sender(): ?User
    {
        /** @var User|null $sender */
        $sender = $this->belongsTo(User::class, 'sender_id');

        return $sender;
    }

    public function recipient(): ?User
    {
        /** @var User|null $recipient */
        $recipient = $this->belongsTo(User::class, 'recipient_id');

        return $recipient;
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public static function paginateInbox(int $userId, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $count = static::connection()->selectOne(
            'SELECT COUNT(*) AS n FROM ('
            . ' SELECT CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END AS other_user_id'
            . ' FROM private_messages'
            . ' WHERE sender_id = ? OR recipient_id = ?'
            . ' GROUP BY other_user_id'
            . ' ) x',
            [$userId, $userId, $userId],
        );
        $total = (int) ($count['n'] ?? 0);

        $items = static::connection()->select(
            'SELECT convo.other_user_id, convo.unread_count, convo.last_created_at,'
            . ' pm.body AS last_body, pm.sender_id AS last_sender_id,'
            . ' u.name AS other_user_name, u.avatar AS other_user_avatar'
            . ' FROM ('
            . '   SELECT'
            . '     CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END AS other_user_id,'
            . '     MAX(id) AS last_message_id,'
            . '     MAX(created_at) AS last_created_at,'
            . '     SUM(CASE WHEN recipient_id = ? AND read_at IS NULL THEN 1 ELSE 0 END) AS unread_count'
            . '   FROM private_messages'
            . '   WHERE sender_id = ? OR recipient_id = ?'
            . '   GROUP BY other_user_id'
            . ' ) convo'
            . ' INNER JOIN private_messages pm ON pm.id = convo.last_message_id'
            . ' INNER JOIN users u ON u.id = convo.other_user_id'
            . ' ORDER BY convo.last_created_at DESC, pm.id DESC'
            . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            [$userId, $userId, $userId, $userId],
        );

        return ['items' => $items, 'total' => $total];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public static function paginateConversation(int $authUserId, int $otherUserId, int $page, int $perPage = 30): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $count = static::connection()->selectOne(
            'SELECT COUNT(*) AS n FROM private_messages'
            . ' WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)',
            [$authUserId, $otherUserId, $otherUserId, $authUserId],
        );
        $total = (int) ($count['n'] ?? 0);

        $items = static::connection()->select(
            'SELECT pm.*, u.name AS sender_name, u.avatar AS sender_avatar'
            . ' FROM private_messages pm'
            . ' INNER JOIN users u ON u.id = pm.sender_id'
            . ' WHERE (pm.sender_id = ? AND pm.recipient_id = ?) OR (pm.sender_id = ? AND pm.recipient_id = ?)'
            . ' ORDER BY pm.created_at DESC, pm.id DESC'
            . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            [$authUserId, $otherUserId, $otherUserId, $authUserId],
        );

        return ['items' => $items, 'total' => $total];
    }

    public static function markConversationRead(int $recipientId, int $senderId): void
    {
        $now = gmdate('Y-m-d H:i:s');
        static::connection()->execute(
            'UPDATE private_messages'
            . ' SET read_at = ?, updated_at = ?'
            . ' WHERE recipient_id = ? AND sender_id = ? AND read_at IS NULL',
            [$now, $now, $recipientId, $senderId],
        );
    }

    public static function unreadCountForUser(int $userId): int
    {
        $row = static::connection()->selectOne(
            'SELECT COUNT(*) AS n FROM private_messages WHERE recipient_id = ? AND read_at IS NULL',
            [$userId],
        );

        return (int) ($row['n'] ?? 0);
    }
}
