O=tuple
I=int
import cv2 as B,mediapipe as T,numpy as A
K=T.solutions.hands
U=K.Hands()
G,H,C=800,600,15
D=A.array([G//2,H//2],dtype=A.float32)
E=A.array([4,4],dtype=A.float32)
L=B.VideoCapture(0)
if not L.isOpened():exit('Error stream')
while True:
	V,F=L.read()
	if not V:break
	F=B.flip(B.resize(F,(G,H)),1);P=U.process(B.cvtColor(F,B.COLOR_BGR2RGB));Q=[A.array([I(B.landmark[K.HandLandmark.INDEX_FINGER_TIP].x*G),I(B.landmark[K.HandLandmark.INDEX_FINGER_TIP].y*H)],dtype=A.float32)for B in P.multi_hand_landmarks]if P.multi_hand_landmarks else[]
	if len(Q)==2:
		J,R=Q;B.line(F,O(J.astype(I)),O(R.astype(I)),(0,255,0),5);M=R-J;N=M/A.linalg.norm(M);S=A.dot(D-J,N);W=J+S*N
		if 0<=S<=A.linalg.norm(M)and A.linalg.norm(D-W)<=C:E=-E+N*A.array([0,.5],dtype=A.float32)
	D+=E;D=A.clip(D,[C,C],[G-C,H-C])
	if D[0]<=C or D[0]>=G-C:E[0]=-E[0]
	if D[1]<=C or D[1]>=H-C:E[1]=-E[1]
	B.circle(F,O(D.astype(I)),C,(0,0,255),-1);B.imshow('Hand Pong Game',F)
	if B.waitKey(1)&255==ord('q'):break
L.release()
B.destroyAllWindows()