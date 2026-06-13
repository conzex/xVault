import nodemailer from 'nodemailer';
import dotenv from 'dotenv';

dotenv.config();

const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST,
  port: parseInt(process.env.SMTP_PORT || '465'),
  secure: true,
  auth: {
    user: process.env.SMTP_USER,
    pass: process.env.SMTP_PASS,
  },
});

export async function sendShareLink(email, link, expiresIn) {
  try {
    await transporter.sendMail({
      from: `"xVault Security" <${process.env.SMTP_USER}>`,
      to: email,
      subject: '🔐 Secure Vault Access Link',
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
          <h2 style="color: #1a1a1a;">Secure Vault Access</h2>
          <p>You have been granted access to a secure password vault via xVault.</p>
          <p><strong>Link expires in: ${expiresIn}</strong></p>
          <div style="margin: 30px 0;">
            <a href="${link}" style="background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">Access Vault</a>
          </div>
          <p style="color: #666; font-size: 14px;">Or copy this link: <br/> ${link}</p>
          <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;" />
          <p style="color: #999; font-size: 12px;">© 2024 Xvault. A Cogent Global Product</p>
        </div>
      `,
    });
    return true;
  } catch (error) {
    console.error('Mailer Error:', error);
    return false;
  }
}

export async function sendVerificationEmail(email, token) {
  const link = `${process.env.CLIENT_URL}/verify-email?token=${token}`;
  try {
    await transporter.sendMail({
      from: `"xVault Security" <${process.env.SMTP_USER}>`,
      to: email,
      subject: '📧 Verify Your xVault Account',
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
          <h2 style="color: #1a1a1a;">Welcome to xVault</h2>
          <p>Please verify your email address to activate your account.</p>
          <div style="margin: 30px 0;">
            <a href="${link}" style="background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">Verify Email</a>
          </div>
          <p style="color: #666; font-size: 14px;">Or copy this link: <br/> ${link}</p>
          <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;" />
          <p style="color: #999; font-size: 12px;">© 2024 Xvault. A Cogent Global Product</p>
        </div>
      `,
    });
    return true;
  } catch (error) {
    console.error('Mailer Error:', error);
    return false;
  }
}

export async function sendPasswordResetEmail(email, token) {
  const link = `${process.env.CLIENT_URL}/reset-password?token=${token}`;
  try {
    await transporter.sendMail({
      from: `"xVault Security" <${process.env.SMTP_USER}>`,
      to: email,
      subject: '🔑 Password Reset Request',
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
          <h2 style="color: #1a1a1a;">Password Reset</h2>
          <p>You requested to reset your password. Click the button below to proceed.</p>
          <p>This link will expire in 1 hour.</p>
          <div style="margin: 30px 0;">
            <a href="${link}" style="background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">Reset Password</a>
          </div>
          <p style="color: #666; font-size: 14px;">Or copy this link: <br/> ${link}</p>
          <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;" />
          <p style="color: #999; font-size: 12px;">© 2024 Xvault. A Cogent Global Product</p>
        </div>
      `,
    });
    return true;
  } catch (error) {
    console.error('Mailer Error:', error);
    return false;
  }
}
