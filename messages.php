<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

require_login();

$user_id = $_SESSION['user_id'];

// Get unique conversations
$query = "SELECT m.*, 
          u.full_name as sender_name, u.profile_image as sender_image,
          r.full_name as receiver_name, r.profile_image as receiver_image
          FROM messages m
          JOIN users u ON m.sender_id = u.user_id
          JOIN users r ON m.receiver_id = r.user_id
          WHERE m.sender_id = ? OR m.receiver_id = ?
          ORDER BY m.sent_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$messages_result = $stmt->get_result();

$page_title = 'My Messages';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Message List -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">Conversations</h5>
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newMessageModal">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
                <div class="card-body p-0 overflow-auto" style="max-height: 600px;">
                    <div class="list-group list-group-flush">
                        <?php if ($messages_result->num_rows > 0): ?>
                            <?php 
                            $seen_users = [];
                            while ($msg = $messages_result->fetch_assoc()): 
                                $other_id = ($msg['sender_id'] == $user_id) ? $msg['receiver_id'] : $msg['sender_id'];
                                if (in_array($other_id, $seen_users)) continue;
                                $seen_users[] = $other_id;
                                
                                $other_name = ($msg['sender_id'] == $user_id) ? $msg['receiver_name'] : $msg['sender_name'];
                                $other_image = ($msg['sender_id'] == $user_id) ? $msg['receiver_image'] : $msg['sender_image'];
                            ?>
                                <a href="?user=<?php echo $other_id; ?>" class="list-group-item list-group-item-action border-0 py-3 <?php echo isset($_GET['user']) && $_GET['user'] == $other_id ? 'active bg-light' : ''; ?>">
                                    <div class="d-flex align-items-center">
                                        <img src="assets/uploads/profile/<?php echo $other_image ?: 'default-avatar.png'; ?>" alt="" class="rounded-circle mr-3" style="width: 45px; height: 45px; object-fit: cover;">
                                        <div class="w-100 overflow-hidden">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="mb-0 text-truncate <?php echo (isset($_GET['user']) && $_GET['user'] == $other_id) ? '' : 'text-dark'; ?>"><?php echo htmlspecialchars($other_name); ?></h6>
                                                <small class="text-muted"><?php echo time_ago($msg['sent_at']); ?></small>
                                            </div>
                                            <p class="mb-0 small text-truncate <?php echo (isset($_GET['user']) && $_GET['user'] == $other_id) ? '' : 'text-muted'; ?>">
                                                <?php echo ($msg['sender_id'] == $user_id) ? 'You: ' : ''; ?>
                                                <?php echo htmlspecialchars($msg['message']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No conversations yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message View -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <?php if (isset($_GET['user'])): 
                    $target_user_id = intval($_GET['user']);
                    $target_user = get_user_by_id($target_user_id);
                    
                    if ($target_user):
                        // Get messages between current user and target user
                        $chat_query = "SELECT * FROM messages 
                                      WHERE (sender_id = ? AND receiver_id = ?) 
                                      OR (sender_id = ? AND receiver_id = ?) 
                                      ORDER BY sent_at ASC";
                        $stmt = $conn->prepare($chat_query);
                        $stmt->bind_param("iiii", $user_id, $target_user_id, $target_user_id, $user_id);
                        $stmt->execute();
                        $chat_result = $stmt->get_result();
                ?>
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center">
                            <img src="assets/uploads/profile/<?php echo $target_user['profile_image'] ?: 'default-avatar.png'; ?>" alt="" class="rounded-circle mr-3" style="width: 40px; height: 40px; object-fit: cover;">
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($target_user['full_name']); ?></h6>
                                <small class="text-success"><i class="fas fa-circle mr-1" style="font-size: 8px;"></i> Online</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4 bg-light overflow-auto chat-window" id="chatWindow" style="height: 450px;">
                        <?php while ($chat_msg = $chat_result->fetch_assoc()): ?>
                            <div class="d-flex mb-4 <?php echo $chat_msg['sender_id'] == $user_id ? 'justify-content-end' : ''; ?>">
                                <?php if ($chat_msg['sender_id'] != $user_id): ?>
                                    <img src="assets/uploads/profile/<?php echo $target_user['profile_image'] ?: 'default-avatar.png'; ?>" alt="" class="rounded-circle mr-2 mt-auto" style="width: 30px; height: 30px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="p-3 rounded shadow-sm <?php echo $chat_msg['sender_id'] == $user_id ? 'bg-primary text-white' : 'bg-white'; ?>" style="max-width: 75%;">
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($chat_msg['message'])); ?></p>
                                    <div class="text-right">
                                        <small class="<?php echo $chat_msg['sender_id'] == $user_id ? 'text-white-50' : 'text-muted'; ?>" style="font-size: 10px;">
                                            <?php echo date('h:i A', strtotime($chat_msg['sent_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="card-footer bg-white border-top p-3">
                        <form action="api/send_message.php" method="POST" id="chatForm">
                            <input type="hidden" name="receiver_id" value="<?php echo $target_user_id; ?>">
                            <div class="input-group">
                                <input type="text" name="message" class="form-control border-0 bg-light" placeholder="Type a message..." required>
                                <div class="input-group-append">
                                    <button class="btn btn-primary px-4" type="submit">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-5">
                        <div class="text-center py-5">
                            <i class="fas fa-comments fa-4x text-light mb-4"></i>
                            <h4>Select a conversation</h4>
                            <p class="text-muted">Choose a message from the list or start a new conversation.</p>
                            <button class="btn btn-primary mt-3" data-toggle="modal" data-target="#newMessageModal">
                                Start New Conversation
                            </button>
                        </div>
                    </div>
                <?php endif; else: ?>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-5">
                       <div class="text-center py-5">
                            <i class="fas fa-comments fa-4x text-light mb-4"></i>
                            <h4>Select a conversation</h4>
                            <p class="text-muted">Choose a message from the list or start a new conversation.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal for New Message would go here -->

<script>
    // Scroll chat to bottom
    const chatWindow = document.getElementById('chatWindow');
    if (chatWindow) {
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }
</script>

<?php include 'includes/footer.php'; ?>
