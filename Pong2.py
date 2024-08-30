import cv2
import mediapipe as mp
import numpy as np
mp_hands = mp.solutions.hands
hands = mp_hands.Hands()
width, height, ball_radius = 800, 600, 15
ball_pos = np.array([width // 2, height // 2], dtype=np.float32)
ball_speed = np.array([4, 4], dtype=np.float32)
cap = cv2.VideoCapture(0)
if not cap.isOpened(): exit("Error stream")
while True:
    ret, frame = cap.read()
    if not ret: break
    frame = cv2.flip(cv2.resize(frame, (width, height)), 1)
    results = hands.process(cv2.cvtColor(frame, cv2.COLOR_BGR2RGB))
    index_finger_tips = [
        np.array([int(hand.landmark[mp_hands.HandLandmark.INDEX_FINGER_TIP].x * width),
                   int(hand.landmark[mp_hands.HandLandmark.INDEX_FINGER_TIP].y * height)], dtype=np.float32)
        for hand in results.multi_hand_landmarks
    ] if results.multi_hand_landmarks else []
    if len(index_finger_tips) == 2:
        tip1, tip2 = index_finger_tips
        cv2.line(frame, tuple(tip1.astype(int)), tuple(tip2.astype(int)), (0, 255, 0), 5)
        line_vec = tip2 - tip1
        line_unit_vec = line_vec / np.linalg.norm(line_vec)
        proj_length = np.dot(ball_pos - tip1, line_unit_vec)
        proj_point = tip1 + proj_length * line_unit_vec     
        if 0 <= proj_length <= np.linalg.norm(line_vec) and np.linalg.norm(ball_pos - proj_point) <= ball_radius:
            ball_speed = -ball_speed + line_unit_vec * np.array([0, 0.5], dtype=np.float32)
    ball_pos += ball_speed
    ball_pos = np.clip(ball_pos, [ball_radius, ball_radius], [width - ball_radius, height - ball_radius])
    if ball_pos[0] <= ball_radius or ball_pos[0] >= width - ball_radius:
        ball_speed[0] = -ball_speed[0]
    if ball_pos[1] <= ball_radius or ball_pos[1] >= height - ball_radius:
        ball_speed[1] = -ball_speed[1]
    cv2.circle(frame, tuple(ball_pos.astype(int)), ball_radius, (0, 0, 255), -1)
    cv2.imshow('Hand Pong Game', frame)
    if cv2.waitKey(1) & 0xFF == ord('q'): break
cap.release()
cv2.destroyAllWindows()